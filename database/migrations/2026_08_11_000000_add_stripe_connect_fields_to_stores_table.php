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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->index()->after('address_settings');
            $table->string('stripe_account_type')->nullable()->after('stripe_account_id');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_account_type');
            $table->boolean('stripe_charges_enabled')->default(false)->after('stripe_details_submitted');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_charges_enabled');
            $table->timestamp('stripe_onboarded_at')->nullable()->after('stripe_payouts_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_account_type',
                'stripe_details_submitted',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_onboarded_at',
            ]);
        });
    }
};
