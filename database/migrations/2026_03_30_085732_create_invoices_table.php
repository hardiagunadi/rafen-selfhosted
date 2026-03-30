<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('ppp_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ppp_profile_id')->nullable()->constrained('ppp_profiles')->nullOnDelete();
            $table->string('customer_id', 120)->nullable();
            $table->string('customer_name', 150)->nullable();
            $table->string('tipe_service', 30)->nullable();
            $table->string('paket_langganan', 150)->nullable();
            $table->decimal('harga_dasar', 12, 2)->default(0);
            $table->decimal('harga_asli', 12, 2)->default(0);
            $table->decimal('ppn_percent', 5, 2)->default(0);
            $table->decimal('ppn_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->boolean('promo_applied')->default(false);
            $table->boolean('prorata_applied')->default(false);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('unpaid');
            $table->boolean('renewed_without_payment')->default(false);
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_channel', 60)->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('transfer_amount', 12, 2)->nullable();
            $table->text('payment_note')->nullable();
            $table->string('payment_token', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
