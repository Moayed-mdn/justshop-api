<?php

use App\Http\Controllers\Api\Merchant\ShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Merchant Shipping Management Routes
|--------------------------------------------------------------------------
|
| Routes for merchants to manage their store's shipping configuration:
| - Store address settings (allowed countries, validation rules)
| - Shipping zones (geographic groupings)
| - Shipping methods (delivery options with pricing)
| - Zone-method assignments (zone-specific pricing overrides)
|
*/

Route::name('merchant.shipping.')
    ->prefix('stores/{store}')
    ->middleware([
        'auth:sanctum',
        'verified',
        'identity.route:merchant_admin,merchant,enforce',
        'store.context',
    ])
    ->group(function () {
    
    // Store Address Settings
    Route::prefix('shipping/address-settings')->group(function () {
        Route::get('/', [ShippingController::class, 'getAddressSettings'])->name('address-settings.show');
        Route::put('/', [ShippingController::class, 'updateAddressSettings'])->name('address-settings.update');
    });
    
    // Shipping Zones
    Route::prefix('shipping/zones')->group(function () {
        Route::get('/', [ShippingController::class, 'listZones'])->name('zones.index');
        Route::post('/', [ShippingController::class, 'createZone'])->name('zones.store');
        Route::put('/{zone}', [ShippingController::class, 'updateZone'])->name('zones.update');
        Route::delete('/{zone}', [ShippingController::class, 'deleteZone'])->name('zones.destroy');
        
        // Zone-Method Assignment
        Route::post('/{zone}/methods', [ShippingController::class, 'assignMethodToZone'])->name('zones.methods.store');
        Route::delete('/{zone}/methods/{method}', [ShippingController::class, 'removeMethodFromZone'])->name('zones.methods.destroy');
        Route::put('/{zone}/methods/{method}', [ShippingController::class, 'updateZoneMethodPrice'])->name('zones.methods.update');
    });
    
    // Shipping Methods
    Route::prefix('shipping/methods')->group(function () {
        Route::get('/', [ShippingController::class, 'listMethods'])->name('methods.index');
        Route::post('/', [ShippingController::class, 'createMethod'])->name('methods.store');
        Route::put('/{method}', [ShippingController::class, 'updateMethod'])->name('methods.update');
        Route::delete('/{method}', [ShippingController::class, 'deleteMethod'])->name('methods.destroy');
    });
});
