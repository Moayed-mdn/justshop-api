<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for the Shopify-inspired checkout system:
     * - store_address_settings: Store-specific address validation rules
     * - shipping_methods: Available shipping methods per store
     * - shipping_zones: Geographic zones for shipping
     * - shipping_zone_methods: Pivot table linking zones and methods
     */
    public function up(): void
    {
        // 1. Store Address Settings Table
        Schema::create('store_address_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->json('allowed_countries')->comment('ISO 2-letter country codes allowed for shipping');
            $table->json('required_fields')->comment('Address fields required for this store');
            $table->json('validation_rules')->nullable()->comment('Custom validation rules per field');
            $table->boolean('require_phone')->default(false);
            $table->boolean('require_company')->default(false);
            $table->boolean('allow_po_boxes')->default(true);
            $table->timestamps();
            
            $table->unique('store_id');
            $table->index('store_id');
        });

        // 2. Shipping Methods Table
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Display name of shipping method');
            $table->string('code')->comment('Unique code for internal reference');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->comment('Base shipping price');
            $table->string('currency', 3)->default('USD');
            $table->decimal('min_order_amount', 10, 2)->nullable()->comment('Minimum order amount for this method');
            $table->decimal('max_order_amount', 10, 2)->nullable()->comment('Maximum order amount for this method');
            $table->integer('estimated_delivery_days')->nullable()->comment('Estimated delivery time in days');
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0)->comment('Display order in checkout');
            $table->timestamps();
            
            $table->index(['store_id', 'is_active']);
            $table->index('store_id');
            $table->unique(['store_id', 'code']);
        });

        // 3. Shipping Zones Table
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Zone name (e.g., "North America", "EU")');
            $table->json('countries')->comment('ISO 2-letter country codes in this zone');
            $table->json('regions')->nullable()->comment('Specific states/provinces within countries');
            $table->json('postal_code_patterns')->nullable()->comment('Regex patterns for postal codes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('store_id');
            $table->index(['store_id', 'is_active']);
        });

        // 4. Shipping Zone Methods Pivot Table
        Schema::create('shipping_zone_methods', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable()->comment('Override base price for this zone');
            $table->timestamps();
            
            $table->primary(['shipping_zone_id', 'shipping_method_id'], 'zone_method_primary');
            $table->index('shipping_zone_id');
            $table->index('shipping_method_id');
        });

        // 5. Add shipping_method_id to orders table if not exists
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'shipping_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('shipping_method_id')
                    ->nullable()
                    ->after('shipping_method')
                    ->constrained('shipping_methods')
                    ->nullOnDelete();
                $table->index('shipping_method_id');
            });
        }

        // 6. Update addresses table to add default flags
        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                // Drop old is_default column if exists and add new specific columns
                if (Schema::hasColumn('addresses', 'is_default')) {
                    $table->dropColumn('is_default');
                }
                
                if (!Schema::hasColumn('addresses', 'is_default_shipping')) {
                    $table->boolean('is_default_shipping')->default(false)->after('type');
                }
                
                if (!Schema::hasColumn('addresses', 'is_default_billing')) {
                    $table->boolean('is_default_billing')->default(false)->after('is_default_shipping');
                }
                
                // Add address name for easier identification
                if (!Schema::hasColumn('addresses', 'name')) {
                    $table->string('name')->nullable()->after('user_id')->comment('Friendly name like "Home" or "Office"');
                }
                
                // Add indexes for default addresses
                $table->index(['user_id', 'is_default_shipping']);
                $table->index(['user_id', 'is_default_billing']);
                $table->index(['user_id', 'store_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order to respect foreign keys
        Schema::dropIfExists('shipping_zone_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('store_address_settings');

        // Remove shipping_method_id from orders if it was added
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'shipping_method_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['shipping_method_id']);
                $table->dropColumn('shipping_method_id');
            });
        }

        // Restore old addresses structure
        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                if (Schema::hasColumn('addresses', 'name')) {
                    $table->dropColumn('name');
                }
                
                if (Schema::hasColumn('addresses', 'is_default_shipping')) {
                    $table->dropColumn('is_default_shipping');
                }
                
                if (Schema::hasColumn('addresses', 'is_default_billing')) {
                    $table->dropColumn('is_default_billing');
                }
                
                // Restore old is_default column
                $table->boolean('is_default')->default(false);
            });
        }
    }
};
