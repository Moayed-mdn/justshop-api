<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_block_instances', function (Blueprint $table) {
            $table->id();
            $table->morphs('container');
            $table->string('type');
            $table->string('name')->nullable();
            $table->json('settings')->nullable();
            $table->json('content')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_block_instances');
    }
};
