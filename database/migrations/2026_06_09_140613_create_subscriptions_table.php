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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_account_id')->constrained('billing_accounts')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->foreignId('plan_price_id')->nullable()->constrained('plan_prices');

            $table->string('status');               // SubscriptionStatusEnum
            $table->string('billing_cycle');        // BillingCycleEnum

            // Provider linkage
            $table->string('provider')->default('stripe');
            $table->string('provider_subscription_id')->nullable(); // sub_xxx
            $table->string('provider_status')->nullable();          // raw provider status mirror
            $table->timestamp('provider_synced_at')->nullable();    // last webhook sync

            // Trial
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Billing period
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();

            // Lifecycle
            $table->timestamp('grace_period_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->boolean('collection_paused')->default(false);
            $table->timestamp('ended_at')->nullable();

            // Scheduled plan change
            $table->foreignId('pending_plan_id')->nullable()->constrained('plans');
            $table->timestamp('pending_plan_effective_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['billing_account_id', 'status']);
            $table->index(['provider', 'provider_subscription_id']);
            $table->unique('provider_subscription_id');
            $table->index('status');
            $table->index('trial_ends_at');
            $table->index('current_period_ends_at');
            $table->index('grace_period_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
