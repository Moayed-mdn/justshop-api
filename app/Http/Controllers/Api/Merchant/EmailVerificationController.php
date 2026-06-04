<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Actions\Auth\GetEmailVerificationStatusAction;
use App\Http\Resources\Auth\EmailVerificationStatusResource;
use App\Traits\ApiResponserTrait;

class EmailVerificationController extends Controller
{
    use ApiResponserTrait;

    public function __construct(private readonly GetEmailVerificationStatusAction $getEmailVerificationStatusAction) {}

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $dto = $this->getEmailVerificationStatusAction->execute($user);

        if (!$dto->emailVerified) {
            return $this->error(
                __('auth.email_not_yet_verified'),
                422,
                null,
                'AUTH_003',
            );
        }

        return $this->success(new EmailVerificationStatusResource($dto));
    }
}
