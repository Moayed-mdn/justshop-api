<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Actions\Theme\CreateTemplateAction;
use App\Actions\Theme\DeleteTemplateAction;
use App\Actions\Theme\DuplicateTemplateAction;
use App\Actions\Theme\UpdateTemplateAction;
use App\DTOs\Theme\CreateTemplateDTO;
use App\DTOs\Theme\UpdateTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Theme\CreateTemplateRequest;
use App\Http\Requests\Theme\UpdateTemplateRequest;
use App\Http\Resources\Theme\PageTemplateCollection;
use App\Http\Resources\Theme\PageTemplateResource;
use App\Models\PageTemplate;
use App\Models\Store;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;

class ThemeTemplateController extends Controller
{
    use ApiResponserTrait;

    public function __construct(
        private readonly CreateTemplateAction $createTemplateAction,
        private readonly UpdateTemplateAction $updateTemplateAction,
        private readonly DeleteTemplateAction $deleteTemplateAction,
        private readonly DuplicateTemplateAction $duplicateTemplateAction,
    ) {}

    /**
     * List all templates for a store
     */
    public function index(int $store): JsonResponse
    {
        $storeModel = Store::findOrFail($store);
        $this->authorize('viewAny', [PageTemplate::class, $storeModel]);

        $templates = PageTemplate::where('store_id', $store)
            ->withCount('pages')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();

        return $this->success(new PageTemplateCollection($templates));
    }

    /**
     * Create a new template
     */
    public function store(CreateTemplateRequest $request, int $store): JsonResponse
    {
        $storeModel = Store::findOrFail($store);
        $this->authorize('create', [PageTemplate::class, $storeModel]);

        $template = $this->createTemplateAction->execute(
            CreateTemplateDTO::fromRequest($request, $store)
        );

        return $this->success(new PageTemplateResource($template), 201);
    }

    /**
     * Show a specific template
     */
    public function show(int $store, int $template): JsonResponse
    {
        $template = PageTemplate::where('store_id', $store)
            ->findOrFail($template);

        $this->authorize('view', $template);

        return $this->success(new PageTemplateResource($template));
    }

    /**
     * Update a template
     */
    public function update(UpdateTemplateRequest $request, int $store, int $template): JsonResponse
    {
        $template = PageTemplate::where('store_id', $store)
            ->findOrFail($template);

        $this->authorize('update', $template);

        $updatedTemplate = $this->updateTemplateAction->execute(
            $template,
            UpdateTemplateDTO::fromRequest($request)
        );

        return $this->success(new PageTemplateResource($updatedTemplate));
    }

    /**
     * Delete a template
     */
    public function destroy(int $store, int $template): JsonResponse
    {
        $template = PageTemplate::where('store_id', $store)
            ->findOrFail($template);

        $this->authorize('delete', $template);

        $this->deleteTemplateAction->execute($template);

        return $this->success(null, 204);
    }

    /**
     * Duplicate a template
     */
    public function duplicate(int $store, int $template): JsonResponse
    {
        $template = PageTemplate::where('store_id', $store)
            ->findOrFail($template);

        $storeModel = Store::findOrFail($store);
        $this->authorize('create', [PageTemplate::class, $storeModel]);

        $duplicatedTemplate = $this->duplicateTemplateAction->execute($template);

        return $this->success(new PageTemplateResource($duplicatedTemplate), 201);
    }
}
