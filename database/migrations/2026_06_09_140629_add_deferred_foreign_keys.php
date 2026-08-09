<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreign keys that cannot be declared in the initial table definitions
     * because their target tables are created later or participate in a cycle.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('billing_account_id')
                ->references('id')
                ->on('billing_accounts')
                ->nullOnDelete();

            $table->foreign('last_active_store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('active_theme_id')
                ->references('id')
                ->on('themes')
                ->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['active_theme_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_active_store_id']);
            $table->dropForeign(['billing_account_id']);
        });
    }
};
