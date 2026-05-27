<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Auth\SocialAuthCallbackAction;
use App\Actions\Auth\SocialAuthRedirectAction;
use App\DTOs\Auth\SocialAuthCallbackDTO;
use App\DTOs\Auth\SocialAuthRedirectDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialAuthCallbackRequest;
use App\Http\Requests\Auth\SocialAuthRedirectRequest;
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private SocialAuthRedirectAction $socialAuthRedirectAction,
        private SocialAuthCallbackAction $socialAuthCallbackAction,
    ) {}

    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirect(SocialAuthRedirectRequest $request): RedirectResponse
    {
        return $this->socialAuthRedirectAction->execute(
            SocialAuthRedirectDTO::fromRequest($request)
        );
    }

    /**
     * Handle the callback from Google.
     */
    public function callback(SocialAuthCallbackRequest $request): RedirectResponse
    {
        return $this->socialAuthCallbackAction->execute(
            SocialAuthCallbackDTO::fromRequest($request)
        );
    }
}
