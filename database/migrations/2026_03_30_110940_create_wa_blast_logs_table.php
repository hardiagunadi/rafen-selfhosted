<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_blast_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sent_by_name', 150)->nullable();
            $table->string('event', 50)->default('blast')->index();
            $table->string('target_type', 50)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_normalized', 30)->nullable();
            $table->string('status', 20)->index();
            $table->string('reason')->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->string('ref_id', 100)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_blast_logs');
    }
};
