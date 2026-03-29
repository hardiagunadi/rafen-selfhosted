<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpe_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('radius_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('olt_onu_optic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('genieacs_device_id', 191)->unique();
            $table->string('param_profile', 32)->nullable();
            $table->string('serial_number', 191)->nullable();
            $table->string('manufacturer', 191)->nullable();
            $table->string('model', 191)->nullable();
            $table->string('firmware_version', 191)->nullable();
            $table->string('status', 32)->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->string('mac_address', 32)->nullable();
            $table->json('cached_params')->nullable();
            $table->timestamps();

            $table->index(['radius_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpe_devices');
    }
};
