<?php

use App\Http\Controllers\Api\Platform\AdminDocumentController;
use App\Http\Controllers\Api\Platform\AdminDocumentSectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('cms')->group(function () {
    // Documents
    Route::prefix('docs')
        ->name('platform.cms.docs.')
        ->controller(AdminDocumentController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/{id}/publish', 'publish')->name('publish');
            Route::post('/reorder', 'reorder')->name('reorder');
        });

    // Sections
    Route::prefix('doc-sections')
        ->name('platform.cms.doc-sections.')
        ->controller(AdminDocumentSectionController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });
});
