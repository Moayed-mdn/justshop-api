<?php

namespace App\Services;

use App\Models\User;
use App\Services\Auth\GuardShadowAnalyzer;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionGuardTelemetry;
use App\Services\Auth\SessionOwnershipResolver;
use App\Support\System\FrontendUrlBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthService
{
    public function __construct(
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionOwnershipResolver $sessionOwnershipResolver,
        private readonly GuardShadowAnalyzer $guardShadowAnalyzer,
        private readonly SessionGuardTelemetry $sessionGuardTelemetry,
    ) {}

    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect(): RedirectResponse
    {
        $request = request();
        FrontendUrlBuilder::rememberSocialAuthFrontendUrl($request);

        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(): RedirectResponse
    {
        $request = request();
        $frontendBaseUrl = FrontendUrlBuilder::pullSocialAuthFrontendUrl($request)
            ?? FrontendUrlBuilder::resolveRequestFrontendUrl($request);
        $isStorefrontProxyCallback = $request->headers->has('X-Storefront-Version');

        // When Google returns to Laravel directly, bounce once through the Nuxt callback
        // proxy so the frontend host can receive the session cookies.
        if (!$isStorefrontProxyCallback && ($request->has('code') || $request->has('state'))) {
            return redirect(FrontendUrlBuilder::build('/api/auth/google/callback', $request->query(), $frontendBaseUrl));
        }

        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth failed', ['error' => $e->getMessage()]);
            
            return redirect(FrontendUrlBuilder::build(
                '/auth/google/callback',
                ['error' => 'google_auth_failed'],
                $frontendBaseUrl,
            ));
        }

        $user = $this->findOrCreateUser($googleUser);

        Auth::login($user);
        $identityContext = $this->identityContextResolver->resolve($user);
        $ownership = $this->sessionOwnershipResolver->resolve($request, $identityContext);
        $this->sessionGuardTelemetry->logSessionOwnershipResolved($request, $ownership);

        $request->session()->regenerate();

        return redirect(FrontendUrlBuilder::build(
            '/auth/google/callback',
            ['user_id' => $user->id],
            $frontendBaseUrl,
        ));
    }

    /**
     * Find existing user or create a new one.
     */
    private function findOrCreateUser($googleUser): User
    {
        $user = User::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar'    => $googleUser->getAvatar(),
                ]);
            }
            return $user;
        }

        return User::create([
            'name'              => $googleUser->getName(),
            'email'             => $googleUser->getEmail(),
            'google_id'         => $googleUser->getId(),
            'avatar'            => $googleUser->getAvatar(),
            'email_verified_at' => now(),
        ]);
    }
}
