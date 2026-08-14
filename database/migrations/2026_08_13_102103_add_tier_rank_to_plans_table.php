<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add tier_rank column as nullable first
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('tier_rank')->nullable()->after('tier');
        });

        // Backfill existing rows: starter → 1, growth → 2, enterprise → 3
        DB::table('plans')->where('tier', 'starter')->update(['tier_rank' => 1]);
        DB::table('plans')->where('tier', 'growth')->update(['tier_rank' => 2]);
        DB::table('plans')->where('tier', 'enterprise')->update(['tier_rank' => 3]);

        // Make tier_rank not nullable
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('tier_rank')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('tier_rank');
        });
    }
};
