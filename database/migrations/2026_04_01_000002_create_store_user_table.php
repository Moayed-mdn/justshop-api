<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('role');
            $table->string('lifecycle_status')
                ->default('active')
                ->index()
                ->comment('Wave 6: MembershipLifecycleEnum value');
            $table->timestamp('lifecycle_changed_at')->nullable();
            $table->string('lifecycle_changed_by_actor_type')->nullable();
            $table->unsignedBigInteger('lifecycle_changed_by_actor_id')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_user');
    }
};
