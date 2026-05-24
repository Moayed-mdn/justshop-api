<?php

use App\Enums\Store\ProvisioningStatusEnum;
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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('provisioning_status')->default(ProvisioningStatusEnum::PENDING->value)->after('status_changed_by_actor_id');
            $table->integer('provisioning_progress')->default(0)->after('provisioning_status');
            $table->string('provisioning_current_step')->nullable()->after('provisioning_progress');
            $table->text('provisioning_message')->nullable()->after('provisioning_current_step');
            $table->boolean('provisioning_retryable')->default(false)->after('provisioning_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'provisioning_status',
                'provisioning_progress',
                'provisioning_current_step',
                'provisioning_message',
                'provisioning_retryable',
            ]);
        });
    }
};
