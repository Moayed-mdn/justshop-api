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
        Schema::create('billing_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('billing_email');
            $table->string('legal_name')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('default_currency', 3)->default('USD');
            $table->string('tax_id')->nullable();
            $table->string('status')->default('active'); // BillingAccountStatusEnum
            $table->boolean('trial_used')->default(false); // prevents trial gaming
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('owner_user_id');    // one billing account per owner
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_accounts');
    }
};
