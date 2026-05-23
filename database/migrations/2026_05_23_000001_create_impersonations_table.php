<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wave 6: Impersonation Governance Table
     * 
     * Stores governed impersonation lifecycle.
     */
    public function up(): void
    {
        Schema::create('impersonations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_id')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->string('status')->index(); // pending, active, terminated, expired, denied
            $table->dateTime('requested_at');
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('terminated_at')->nullable();
            $table->dateTime('expires_at')->index();
            $table->string('termination_reason')->nullable();
            $table->string('approval_token')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->timestamps();

            $table->index(['initiator_id', 'status']);
            $table->index(['target_id', 'status']);
            $table->index(['session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonations');
    }
};
