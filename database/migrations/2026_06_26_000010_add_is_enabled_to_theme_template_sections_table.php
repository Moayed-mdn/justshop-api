<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('theme_template_sections', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->after('overrides');
        });
    }

    public function down(): void
    {
        Schema::table('theme_template_sections', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
