<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number', 40)->unique();
            $table->string('payment_type', 20)->default('invoice');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_channel', 60)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->string('reference', 120)->nullable();
            $table->string('merchant_ref', 120)->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('callback_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payment_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
