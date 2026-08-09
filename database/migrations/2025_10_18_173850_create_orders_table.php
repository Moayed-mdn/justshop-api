<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email')->nullable();
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->json('shipping_address_data')->nullable();
            
            // Order totals with better precision for international currencies
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('currency', 3)->default('USD');
            
            // Status strings (enforced via PHP Enums)
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            
            // Payment
            $table->string('payment_intent_id')->nullable();
            $table->string('stripe_checkout_session_id')->nullable()->unique(); // Stripe payment intent ID
            $table->string('payment_method_type')->nullable(); // card, paypal, etc.
            
            // Shipping
            $table->string('shipping_method')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            $table->index('store_id');
            $table->index(['store_id', 'id']);
            $table->index('shipping_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
