<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teknisi_setorans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teknisi_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_date');
            $table->unsignedInteger('total_invoices')->default(0);
            $table->decimal('total_tagihan', 14, 2)->default(0);
            $table->decimal('total_cash', 14, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['teknisi_id', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teknisi_setorans');
    }
};
