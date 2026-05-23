<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionOwnershipManager
{
    private const KEY_AUTH_DOMAIN = 'auth_domain';
    private const KEY_ACTOR_TYPE = 'actor_type';
    private const KEY_ACTOR_ID = 'actor_id';

    public function tag(Request $request, User $user, string $authDomain): void
    {
        $request->session()->put(self::KEY_AUTH_DOMAIN, $authDomain);
        $request->session()->put(self::KEY_ACTOR_TYPE, $user->getActorContext()->value);
        $request->session()->put(self::KEY_ACTOR_ID, (int) $user->id);
    }

    public function getAuthDomain(): ?string
    {
        return Session::get(self::KEY_AUTH_DOMAIN);
    }

    public function getActorType(): ?string
    {
        return Session::get(self::KEY_ACTOR_TYPE);
    }

    public function getActorId(): ?int
    {
        return Session::get(self::KEY_ACTOR_ID);
    }

    public function invalidate(Request $request): void
    {
        $request->session()->forget([
            self::KEY_AUTH_DOMAIN,
            self::KEY_ACTOR_TYPE,
            self::KEY_ACTOR_ID,
        ]);

        // Note: For now we still invalidate the whole session to preserve legacy compatibility
        // unless we detect multiple actors (future phase).
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
