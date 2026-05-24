<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

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

        return $this->success(new EmailVerificationStatusResource($dto));
    }
}
