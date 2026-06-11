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
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('stripe');
            $table->string('provider_event_id');    // evt_xxx
            $table->string('event_type');
            $table->string('status')->default('received'); // WebhookStatusEnum
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->json('payload');                // full raw event for replay
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'webhook_events_provider_unique');
            $table->index('event_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
