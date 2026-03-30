<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('expense_date');
            $table->string('category');
            $table->enum('service_type', ['general', 'pppoe', 'hotspot', 'voucher'])->default('general');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['expense_date', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};
