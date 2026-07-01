<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\Media\DeleteImageAction;
use App\Actions\Admin\Media\UploadImageAction;
use App\DTOs\Admin\Media\DeleteImageDTO;
use App\DTOs\Admin\Media\UploadImageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Media\DeleteImageRequest;
use App\Http\Requests\Admin\Media\UploadImageRequest;
use App\Models\Store;
use Illuminate\Http\JsonResponse;

class AdminMediaController extends Controller
{
    public function __construct(
        private UploadImageAction $uploadAction,
        private DeleteImageAction $deleteAction,
    ) {}

    /**
     * Upload an image.
     *
     * @param  UploadImageRequest  $request
     * @param  int  $store
     * @return JsonResponse
     */
    public function upload(
        UploadImageRequest $request,
        Store $store,
    ): JsonResponse {
        $result = $this->uploadAction->execute(
            UploadImageDTO::fromRequest($request, $store->id)
        );

        return $this->success(
            data: $result,
            message: __('media.upload_success'),
        );
    }

    /**
     * Delete an image.
     *
     * @param  DeleteImageRequest  $request
     * @param  int  $store
     * @return JsonResponse
     */
    public function delete(
        DeleteImageRequest $request,
        Store $store,
    ): JsonResponse {
        $this->deleteAction->execute(
            DeleteImageDTO::fromRequest($request, $store->id)
        );

        return $this->success(
            data: null,
            message: __('media.delete_success'),
        );
    }
}
