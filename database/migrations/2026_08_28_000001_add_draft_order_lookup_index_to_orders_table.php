<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EnhancedCheckoutService now looks up "does this user already have an
 * unpaid draft order for this store?" on every visit to the payment step
 * (see findReusableDraftOrder), and ExpireAbandonedOrdersCommand runs the
 * same shape of query on a schedule. Both filter on
 * (store_id, user_id, status, payment_status), so index that combination
 * rather than relying on the existing single-column store_id index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(
                ['store_id', 'user_id', 'status', 'payment_status'],
                'orders_draft_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_draft_lookup_index');
        });
    }
};
