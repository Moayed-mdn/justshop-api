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
        Schema::create('impersonation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('users')->onDelete('cascade');
            $table->string('reason');
            $table->json('approval_metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['initiator_id', 'revoked_at']);
            $table->index(['target_id', 'revoked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impersonation_records');
    }
};
