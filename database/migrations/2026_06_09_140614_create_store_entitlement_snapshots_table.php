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
        Schema::create('store_entitlement_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('billing_account_id')->constrained('billing_accounts')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            $table->string('entitlement_status');   // EntitlementStatusEnum
            $table->json('features');               // materialized {"products.max": 1000, "analytics.advanced": false}
            $table->json('limits')->nullable();     // current usage {"products.count": 42, "stores.count": 1}
            $table->timestamp('expires_at')->nullable();    // mirrors subscription access boundary
            $table->timestamp('refreshed_at')->nullable();

            $table->timestamps();

            $table->unique('store_id');
            $table->index(['billing_account_id', 'entitlement_status'], 'entitlement_snapshots_account_status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_entitlement_snapshots');
    }
};
