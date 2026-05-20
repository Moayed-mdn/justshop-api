<?php
// database/migrations/2026_03_23_000001_alter_orders_table_for_stripe_checkout.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop the existing foreign key on user_id
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // 2. Alter columns and add new fields
        Schema::table('orders', function (Blueprint $table) {
            // Make user_id nullable for guest checkout
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Guest email (for orders without a user account)
            $table->string('guest_email')->nullable()->after('user_id');

            // Stripe Checkout Session ID (to link webhook events to orders)
            $table->string('stripe_checkout_session_id')->nullable()->unique()->after('payment_intent_id');

            // Shipping address collected by Stripe (JSON snapshot)
            $table->json('shipping_address_data')->nullable()->after('billing_address_id');

        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'stripe_checkout_session_id', 'shipping_address_data', 'currency']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};