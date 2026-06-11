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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key');       // FeatureKeyEnum: 'stores.max', 'products.max', etc.
            $table->string('value_type');        // 'limit' | 'boolean' | 'unlimited'
            $table->bigInteger('limit_value')->nullable();   // null = unlimited
            $table->boolean('boolean_value')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
            $table->index('feature_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
