<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\Admin\Cms\Documentation\AdminDocumentController;
use App\Http\Controllers\Api\Admin\Cms\Documentation\AdminDocumentSectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/cms')->middleware(['auth:sanctum', 'verified', 'role:super_admin'])->group(function () {
    // Documents
    Route::prefix('docs')->group(function () {
        Route::get('/', [AdminDocumentController::class, 'index'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_VIEW);

        Route::post('/', [AdminDocumentController::class, 'store'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_CREATE);

        Route::get('/{id}', [AdminDocumentController::class, 'show'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_VIEW);

        Route::put('/{id}', [AdminDocumentController::class, 'update'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_UPDATE);

        Route::delete('/{id}', [AdminDocumentController::class, 'destroy'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_DELETE);

        Route::post('/{id}/publish', [AdminDocumentController::class, 'publish'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_PUBLISH);

        Route::post('/reorder', [AdminDocumentController::class, 'reorder'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_UPDATE);
    });

    // Sections
    Route::prefix('doc-sections')->group(function () {
        Route::get('/', [AdminDocumentSectionController::class, 'index'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_VIEW);

        Route::post('/', [AdminDocumentSectionController::class, 'store'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_CREATE);

        Route::get('/{id}', [AdminDocumentSectionController::class, 'show'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_VIEW);

        Route::delete('/{id}', [AdminDocumentSectionController::class, 'destroy'])
            ->middleware('permission:' . PermissionEnum::CMS_DOC_DELETE);
    });
});
