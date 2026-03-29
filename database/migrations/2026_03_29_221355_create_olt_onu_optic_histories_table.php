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
        Schema::create('olt_onu_optic_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olt_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('olt_onu_optic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('onu_index');
            $table->string('pon_interface')->nullable();
            $table->string('onu_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('onu_name')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->decimal('rx_onu_dbm', 8, 2)->nullable();
            $table->decimal('tx_onu_dbm', 8, 2)->nullable();
            $table->decimal('rx_olt_dbm', 8, 2)->nullable();
            $table->decimal('tx_olt_dbm', 8, 2)->nullable();
            $table->string('status', 32)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('polled_at')->nullable();
            $table->timestamps();

            $table->index(['olt_connection_id', 'onu_index', 'polled_at']);
            $table->index(['olt_connection_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olt_onu_optic_histories');
    }
};
