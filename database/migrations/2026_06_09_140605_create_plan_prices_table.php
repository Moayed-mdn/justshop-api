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
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('billing_cycle');                // BillingCycleEnum: monthly|annual
            $table->string('currency', 3)->default('USD');
            $table->unsignedBigInteger('amount_cents');     // minor units
            $table->string('provider')->default('stripe');  // provider-agnostic
            $table->string('provider_price_id')->nullable(); // price_xxx (Stripe Price ID)
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // One price per plan/cycle/currency/provider combination
            $table->unique(
                ['plan_id', 'billing_cycle', 'currency', 'provider'],
                'plan_prices_unique'
            );
            $table->unique(['provider', 'provider_price_id'], 'plan_prices_provider_unique');
            $table->index(['plan_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
