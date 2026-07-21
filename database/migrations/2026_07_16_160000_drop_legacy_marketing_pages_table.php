<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('marketing_pages');
    }

    public function down(): void
    {
        Schema::create('marketing_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->unique();
            $table->json('slug');
            $table->json('title');
            $table->json('sections');
            $table->json('seo')->nullable();
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('published_at');
        });
    }
};
