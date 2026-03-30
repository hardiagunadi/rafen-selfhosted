<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_ticket_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('wa_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('image_path')->nullable();
            $table->string('type', 30)->default('note');
            $table->text('meta')->nullable();
            $table->boolean('read_by_cs')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_ticket_notes');
    }
};
