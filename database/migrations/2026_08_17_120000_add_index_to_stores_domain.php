<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⚠️ PERF FIX: `stores.domain` is used to resolve the tenant on every single
 * storefront runtime request (see RuntimeStoreResolver) but had no index at
 * all, forcing a full table scan on every cache-miss lookup. This adds a
 * standard index so cold lookups (first hit per domain, or after the cache
 * TTL expires) stay fast as the number of stores grows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex(['domain']);
        });
    }
};
