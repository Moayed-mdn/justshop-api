<?php

declare(strict_types=1);

namespace App\Actions\Admin\Media;

use App\DTOs\Admin\Media\DeleteImageDTO;
use App\Exceptions\Media\InvalidMediaPathException;
use Illuminate\Support\Facades\Storage;

class DeleteImageAction
{
    /**
     * Execute image deletion.
     *
     * @throws InvalidMediaPathException
     */
    public function execute(DeleteImageDTO $dto): void
    {
        // Security: validate path belongs to the specified context
        if (!$dto->context->validatePath($dto->path)) {
            throw new InvalidMediaPathException(
                __('media.path_context_mismatch', [
                    'context' => $dto->context->value,
                ])
            );
        }

        // Check if file exists and delete
        if (Storage::exists($dto->path)) {
            Storage::delete($dto->path);
        }
    }
}
