<?php

use App\Http\Controllers\Api\Platform\AdminDocumentController;
use App\Http\Controllers\Api\Platform\AdminDocumentSectionController;
use Illuminate\Support\Facades\Route;

// Real documentation controller
$controller = AdminDocumentController::class;

Route::prefix('cms')->group(function () use ($controller) {
    // Documents - Primary route
    Route::prefix('documentation')
        ->name('platform.cms.documentation.')
        ->controller($controller)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/{id}/publish', 'publish')->name('publish');
            Route::post('/{id}/unpublish', 'unpublish')->name('unpublish');
            Route::post('/reorder', 'reorder')->name('reorder');
        });
    
    // Alias: /docs -> /documentation (for frontend compatibility)
    Route::prefix('docs')
        ->name('platform.cms.docs.')
        ->controller($controller)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::post('/{id}/publish', 'publish')->name('publish');
            Route::post('/{id}/unpublish', 'unpublish')->name('unpublish');
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

