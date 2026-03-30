<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('wa_conversations')->cascadeOnDelete();
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->text('message')->nullable();
            $table->string('media_type', 50)->nullable();
            $table->string('media_path')->nullable();
            $table->string('media_mime', 150)->nullable();
            $table->string('media_filename', 255)->nullable();
            $table->string('sender_name', 150)->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('wa_message_id', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_chat_messages');
    }
};
