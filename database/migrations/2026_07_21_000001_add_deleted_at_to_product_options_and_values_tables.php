<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('product_option_values', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('product_option_values', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
