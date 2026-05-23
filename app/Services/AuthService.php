<?php

namespace App\Services;

use App\Exceptions\Auth\EmailVerificationException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\TooManyRequestsException;
use App\Exceptions\Auth\UnauthorizedException;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipResolver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
        private readonly SessionGuardTelemetry $sessionGuardTelemetry,
    ) {}

    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        \Illuminate\Support\Facades\Auth::login($user);
        
        $request = request();
        $identityContext = $this->identityContextResolver->resolve($user);
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext);
        $shadow = $this->guardShadowAnalyzer->analyze($ownership);
        $this->sessionGuardTelemetry->logSessionOwnershipResolved($request, $ownership);

        $request->session()->regenerate();

        return [
            'user' => $user,
        ];
    }

    /**
     * Authenticate a user.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->hasVerifiedEmail()) {
            throw new UnauthorizedException(__('auth.verify_email_before_login'));
        }

        \Illuminate\Support\Facades\Auth::login($user);
        
        $request = request();
        $identityContext = $this->identityContextResolver->resolve($user);
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext);
        $this->sessionGuardTelemetry->logSessionOwnershipResolved($request, $ownership);

        $request->session()->regenerate();

        return [
            'user' => $user,
        ];
    }

    /**
     * Logout a user by invalidating the session.
     */
    public function logout(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user();
        $identityContext = $user ? $this->identityContextResolver->resolve($user) : null;
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext);
        $shadow = $this->guardShadowAnalyzer->analyze($ownership);
        $this->sessionGuardTelemetry->logLogoutOwnership($request, $ownership, $shadow);

        \Illuminate\Support\Facades\Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Verify user email from signed request.
     */
    public function verifyEmail(Request $request): array
    {
        if (!$request->hasValidSignature()) {
            throw new EmailVerificationException(__('auth.invalid_verification_link'));
        }

        $user = User::findOrFail($request->route('id'));

        $expectedHash = sha1($user->getEmailForVerification());
        if (!hash_equals($expectedHash, $request->route('hash'))) {
            throw new EmailVerificationException(__('auth.verification_link_invalid'));
        }

        if ($user->hasVerifiedEmail()) {
            return ['already_verified' => true];
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return ['already_verified' => false];
    }

    /**
     * Resend verification email with rate limiting.
     */
    public function resendVerificationEmail(string $email, string $ip): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new NotFoundException(__('error.user_not_found'));
        }

        if ($user->hasVerifiedEmail()) {
            return ['already_verified' => true];
        }

        $key = 'verification-resend|' . $email . '|' . $ip;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            throw new TooManyRequestsException(trans_choice('auth.too_many_attempts', $seconds, ['seconds' => $seconds]));
        }

        $user->sendEmailVerificationNotification();
        RateLimiter::hit($key, 60);

        return ['already_verified' => false];
    }

    /**
     * Send password reset link.
     */
    public function sendResetLink(array $data): void
    {
        Password::sendResetLink($data);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(array $data): void
    {
        Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
    }
}
