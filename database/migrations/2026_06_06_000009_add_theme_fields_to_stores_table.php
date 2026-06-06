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
            $table->foreignId('active_theme_id')->nullable()->after('timezone')->constrained('themes')->onDelete('set null');
            $table->string('logo_url')->nullable()->after('active_theme_id');
            $table->string('favicon_url')->nullable()->after('logo_url');
            
            // Index for theme lookup
            $table->index('active_theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['active_theme_id']);
            $table->dropIndex(['active_theme_id']);
            $table->dropColumn(['active_theme_id', 'logo_url', 'favicon_url']);
        });
    }
};
