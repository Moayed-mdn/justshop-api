<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\RuntimePageRequest;
use App\Http\Requests\Storefront\RuntimePreviewValidationRequest;
use App\Http\Requests\Storefront\RuntimeResolveRequest;
use App\Services\Storefront\Runtime\StorefrontRuntimeService;

class StorefrontRuntimeController extends Controller
{
    public function __construct(
        private readonly StorefrontRuntimeService $runtimeService,
    ) {}

    public function resolve(RuntimeResolveRequest $request)
    {
        request()->attributes->set('storefront_runtime_artifact', 'route');

        return response()->json($this->runtimeService->resolveRoute(
            path: (string) $request->validated('path'),
            locale: (string) ($request->validated('locale') ?: app()->getLocale()),
        ));
    }

    public function page(RuntimePageRequest $request, string $id)
    {
        request()->attributes->set('storefront_runtime_artifact', 'page');

        return response()->json($this->runtimeService->pagePayload(
            pageId: $id,
            preview: (bool) $request->boolean('preview'),
        ));
    }

    public function navigation()
    {
        request()->attributes->set('storefront_runtime_artifact', 'navigation');

        return response()->json($this->runtimeService->navigationPayload());
    }

    public function theme()
    {
        request()->attributes->set('storefront_runtime_artifact', 'theme');

        return response()->json($this->runtimeService->themePayload());
    }

    public function validatePreview(RuntimePreviewValidationRequest $request)
    {
        request()->attributes->set('storefront_runtime_artifact', 'preview');

        return response()->json($this->runtimeService->validatePreview(
            token: (string) $request->validated('token'),
            pageId: (string) $request->validated('pageId'),
            path: (string) $request->validated('path'),
            locale: (string) $request->validated('locale'),
        ));
    }
}
