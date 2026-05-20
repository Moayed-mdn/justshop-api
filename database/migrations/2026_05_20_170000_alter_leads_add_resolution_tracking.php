<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (!Schema::hasColumn('leads', 'metadata')) {
                $table->json('metadata')->nullable()->after('message');
            }

            if (!Schema::hasColumn('leads', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('archived_at');
            }

            if (!Schema::hasColumn('leads', 'resolved_by')) {
                $table->foreignId('resolved_by')
                    ->nullable()
                    ->after('resolved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'resolved_by')) {
                $table->dropConstrainedForeignId('resolved_by');
            }

            if (Schema::hasColumn('leads', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
        });
    }
};
