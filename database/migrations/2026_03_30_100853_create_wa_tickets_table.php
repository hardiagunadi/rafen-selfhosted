<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('customer_type', 20)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('type', 30)->default('other');
            $table->string('status', 30)->default('open');
            $table->string('priority', 20)->default('normal');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('public_token', 64)->unique();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_tickets');
    }
};
