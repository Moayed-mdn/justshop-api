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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable()->comment('Friendly name like Home or Office');
            $table->string('type')->default('shipping');
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('country');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // For delivery calculations
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('store_id');
            $table->index(['store_id', 'id']);
            $table->index(['user_id', 'is_default_shipping']);
            $table->index(['user_id', 'is_default_billing']);
            $table->index(['user_id', 'store_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
