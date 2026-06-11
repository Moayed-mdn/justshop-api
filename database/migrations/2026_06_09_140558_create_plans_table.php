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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();           // 'starter', 'growth', 'enterprise'
            $table->json('name');                        // {"en": "Starter", "ar": "المبتدئ"}
            $table->json('description')->nullable();
            $table->string('tier');                      // PlanTierEnum: starter|growth|enterprise
            $table->boolean('is_public')->default(true); // false = enterprise/custom (sales-led)
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(14);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();        // badges, highlight text, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
