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
        Schema::table('users', function (Blueprint $table) {
            $table->string('onboarding_step')->nullable()->after('is_active');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
            $table->foreignId('last_active_store_id')
                ->nullable()
                ->after('onboarding_completed_at')
                ->constrained('stores')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_active_store_id']);
            $table->dropColumn([
                'onboarding_step',
                'onboarding_completed_at',
                'last_active_store_id',
            ]);
        });
    }
};
