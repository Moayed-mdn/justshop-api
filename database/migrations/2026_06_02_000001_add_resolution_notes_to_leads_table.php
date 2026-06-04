<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (!Schema::hasColumn('leads', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'resolution_notes')) {
                $table->dropColumn('resolution_notes');
            }
        });
    }
};
