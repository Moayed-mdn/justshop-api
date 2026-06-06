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
        Schema::create('store_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // logo, favicon, banner, image, video, document
            $table->string('file_path'); // Storage path
            $table->string('file_url'); // Public URL
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // In bytes
            $table->integer('width')->nullable(); // For images
            $table->integer('height')->nullable(); // For images
            $table->string('alt_text')->nullable(); // For accessibility
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional metadata (tags, usage, etc.)
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_assets');
    }
};
