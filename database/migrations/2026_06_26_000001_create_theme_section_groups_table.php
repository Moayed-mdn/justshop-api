<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_section_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('handle');
            $table->json('sections');
            $table->json('order');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['theme_id', 'handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_section_groups');
    }
};
