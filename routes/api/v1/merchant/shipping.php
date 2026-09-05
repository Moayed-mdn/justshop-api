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
    // Design requirement: this endpoint accepts a store *slug* only, not a
    // numeric id -- unlike most {store} routes elsewhere, which accept
    // either. See RequireSlugStoreParameter for why this is scoped to just
    // these two routes rather than the shared model binding.
    Route::prefix('shipping/address-settings')
        ->middleware('store.slug_only')
        ->group(function () {
        // Read operations
        Route::get('/', [ShippingController::class, 'getAddressSettings'])->name('address-settings.show');
        
        // Write operations - require active subscription
        Route::put('/', [ShippingController::class, 'updateAddressSettings'])
            ->middleware('subscription.active')
            ->name('address-settings.update');
    });
    
    // Shipping Zones
    Route::prefix('shipping/zones')->group(function () {
        // Read operations
        Route::get('/', [ShippingController::class, 'listZones'])->name('zones.index');
        
        // Write operations - require active subscription
        Route::middleware('subscription.active')->group(function () {
            Route::post('/', [ShippingController::class, 'createZone'])->name('zones.store');
            Route::put('/{zone}', [ShippingController::class, 'updateZone'])->name('zones.update');
            Route::delete('/{zone}', [ShippingController::class, 'deleteZone'])->name('zones.destroy');
            
            // Zone-Method Assignment
            Route::post('/{zone}/methods', [ShippingController::class, 'assignMethodToZone'])->name('zones.methods.store');
            Route::delete('/{zone}/methods/{method}', [ShippingController::class, 'removeMethodFromZone'])->name('zones.methods.destroy');
            Route::put('/{zone}/methods/{method}', [ShippingController::class, 'updateZoneMethodPrice'])->name('zones.methods.update');
        });
    });
    
    // Shipping Methods
    Route::prefix('shipping/methods')->group(function () {
        // Read operations
        Route::get('/', [ShippingController::class, 'listMethods'])->name('methods.index');
        
        // Write operations - require active subscription
        Route::middleware('subscription.active')->group(function () {
            Route::post('/', [ShippingController::class, 'createMethod'])->name('methods.store');
            Route::put('/{method}', [ShippingController::class, 'updateMethod'])->name('methods.update');
            Route::delete('/{method}', [ShippingController::class, 'deleteMethod'])->name('methods.destroy');
        });
    });
});
