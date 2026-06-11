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
        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('plan_price_id')->nullable()->constrained('plan_prices');
            $table->string('provider_item_id')->nullable(); // si_xxx (Stripe Subscription Item)
            $table->unsignedInteger('quantity')->default(1);
            $table->string('item_type')->default('base');   // 'base' | 'addon' | 'metered'
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'item_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_items');
    }
};
