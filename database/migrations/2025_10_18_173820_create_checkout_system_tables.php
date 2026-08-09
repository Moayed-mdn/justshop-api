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
        Schema::create('store_address_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->json('allowed_countries');
            $table->json('required_fields');
            $table->json('validation_rules')->nullable();
            $table->boolean('require_phone')->default(false);
            $table->boolean('require_company')->default(false);
            $table->boolean('allow_po_boxes')->default(true);
            $table->timestamps();

            $table->unique('store_id');
            $table->index('store_id');
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_order_amount', 10, 2)->nullable();
            $table->integer('estimated_delivery_days')->nullable();
            $table->integer('min_delivery_days')->nullable();
            $table->integer('max_delivery_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['store_id', 'is_active']);
            $table->index('store_id');
            $table->unique(['store_id', 'code']);
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('countries');
            $table->json('regions')->nullable();
            $table->json('postal_code_patterns')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('store_id');
            $table->index(['store_id', 'is_active']);
        });

        Schema::create('shipping_zone_methods', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();

            $table->primary(['shipping_zone_id', 'shipping_method_id'], 'zone_method_primary');
            $table->index('shipping_zone_id');
            $table->index('shipping_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_methods');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('store_address_settings');
    }
};
