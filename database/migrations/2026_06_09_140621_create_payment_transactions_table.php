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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_account_id')->constrained('billing_accounts')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            $table->string('provider')->default('stripe');
            $table->string('provider_transaction_id')->nullable(); // pi_xxx or ch_xxx
            $table->string('provider_payment_method_id')->nullable();

            $table->string('type');                 // PaymentTransactionTypeEnum: charge|refund|adjustment
            $table->string('status');               // PaymentStatusEnum
            $table->string('currency', 3);
            $table->bigInteger('amount_cents');     // can be negative for refunds
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_transaction_id'], 'payment_transactions_provider_unique');
            $table->index(['billing_account_id', 'status']);
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
