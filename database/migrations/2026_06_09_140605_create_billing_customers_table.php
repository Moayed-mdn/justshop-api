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
        Schema::create('billing_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_account_id')->constrained('billing_accounts')->cascadeOnDelete();
            $table->string('provider');                         // 'stripe' (future: 'paddle', 'paypal')
            $table->string('provider_customer_id');             // cus_xxx
            $table->string('default_payment_method_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // One customer record per provider per billing account
            $table->unique(['billing_account_id', 'provider'], 'billing_customers_account_provider_unique');
            $table->unique(['provider', 'provider_customer_id'], 'billing_customers_provider_unique');
            $table->index('billing_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_customers');
    }
};
