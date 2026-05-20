<?php

use App\Http\Controllers\Api\Cms\MarketingPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/cms/pages')
    ->controller(MarketingPageController::class)
    ->group(function (): void {
        Route::get('/{slug}', 'show');
    });
