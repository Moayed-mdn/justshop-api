<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Actions\Theme\CreateSystemTemplateAction;
use App\Actions\Theme\DeleteSystemTemplateAction;
use App\Actions\Theme\UpdateSystemTemplateAction;
use App\DTOs\Theme\CreateSystemTemplateDTO;
use App\DTOs\Theme\UpdateSystemTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Theme\CreateSystemTemplateRequest;
use App\Http\Requests\Theme\UpdateSystemTemplateRequest;
use App\Http\Resources\Theme\SystemTemplateCollection;
use App\Http\Resources\Theme\SystemTemplateResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeTemplate;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;

class SystemTemplateController extends Controller
{
    use ApiResponserTrait;

    public function __construct(
        private readonly CreateSystemTemplateAction $createSystemTemplateAction,
        private readonly UpdateSystemTemplateAction $updateSystemTemplateAction,
        private readonly DeleteSystemTemplateAction $deleteSystemTemplateAction,
    ) {}

    public function index(Store $store, Theme $theme): JsonResponse
    {
        $storeModel = $store;
        $this->authorize('viewAny', [ThemeTemplate::class, $storeModel]);

        $templates = $theme->templates()
            ->with('sections.blocks')
            ->orderBy('type')
            ->orderBy('is_default', 'desc')
            ->get();

        return $this->success(new SystemTemplateCollection($templates));
    }

    public function store(CreateSystemTemplateRequest $request, Store $store, Theme $theme): JsonResponse
    {
        $storeModel = $store;
        $this->authorize('create', [ThemeTemplate::class, $storeModel]);

        $template = $this->createSystemTemplateAction->execute(
            CreateSystemTemplateDTO::fromRequest($request, $theme->id)
        );

        return $this->success(new SystemTemplateResource($template), 'success', 201);
    }

    public function show(Store $store, Theme $theme, int $template): JsonResponse
    {
        $template = $theme->templates()
            ->with('sections.blocks')
            ->findOrFail($template);

        $this->authorize('view', $template);

        return $this->success(new SystemTemplateResource($template));
    }

    public function update(UpdateSystemTemplateRequest $request, Store $store, Theme $theme, int $template): JsonResponse
    {
        $template = $theme->templates()->findOrFail($template);

        $this->authorize('update', $template);

        $updated = $this->updateSystemTemplateAction->execute(
            $template,
            UpdateSystemTemplateDTO::fromRequest($request)
        );

        return $this->success(new SystemTemplateResource($updated));
    }

    public function destroy(Store $store, Theme $theme, int $template): JsonResponse
    {
        $template = $theme->templates()->findOrFail($template);

        $this->authorize('delete', $template);

        $this->deleteSystemTemplateAction->execute($template);

        return $this->success(null, 'success', 204);
    }
}
