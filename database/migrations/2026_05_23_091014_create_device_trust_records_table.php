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
        Schema::create('device_trust_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('device_id')->index();
            $table->string('device_type');
            $table->string('ip_address');
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_trust_records');
    }
};
