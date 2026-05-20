<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {

            if (!Schema::hasColumn('product_variants', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('sku');
            }

            if (!Schema::hasColumn('product_variants', 'compare_at_price')) {
                $table->decimal('compare_at_price', 12, 2)->nullable()->after('price');
            }

            if (!Schema::hasColumn('product_variants', 'cost_price')) {
                $table->decimal('cost_price', 12, 2)->nullable()->after('compare_at_price');
            }

            if (!Schema::hasColumn('product_variants', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable()->after('batch_number');
            }

            if (!Schema::hasColumn('product_variants', 'weight_unit')) {
                $table->string('weight_unit', 10)->nullable()->after('weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('product_variants', 'barcode')           ? 'barcode'           : null,
                Schema::hasColumn('product_variants', 'compare_at_price')  ? 'compare_at_price'  : null,
                Schema::hasColumn('product_variants', 'cost_price')        ? 'cost_price'        : null,
                Schema::hasColumn('product_variants', 'weight')            ? 'weight'            : null,
                Schema::hasColumn('product_variants', 'weight_unit')       ? 'weight_unit'       : null,
            ]));
        });
    }
};
