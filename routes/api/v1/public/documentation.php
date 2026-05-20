<?php

use App\Http\Controllers\Api\Cms\Documentation\PublicDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/stores/{store}/cms/docs')->group(function () {
    Route::get('/sidebar', [PublicDocumentController::class, 'sidebar']);
    Route::get('/{slugPath}/navigation', [PublicDocumentController::class, 'navigation'])
        ->where('slugPath', '.*');
    Route::get('/{slugPath}', [PublicDocumentController::class, 'show'])
        ->where('slugPath', '.*');
});
