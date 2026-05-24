<?php

use App\Http\Controllers\Api\Admin\Cms\Documentation\AdminDocumentController;
use App\Http\Controllers\Api\Admin\Cms\Documentation\AdminDocumentSectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin/cms')->middleware(['auth:sanctum', 'verified', 'role:super_admin'])->group(function () {
    // Documents
    Route::prefix('docs')->controller(AdminDocumentController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
        Route::post('/{id}/publish', 'publish');
        Route::post('/reorder', 'reorder');
    });

    // Sections
    Route::prefix('doc-sections')->controller(AdminDocumentSectionController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::delete('/{id}', 'destroy');
    });
});
