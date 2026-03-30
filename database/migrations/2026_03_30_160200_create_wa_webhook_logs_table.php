<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 50);
            $table->string('session_id')->nullable();
            $table->string('sender')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_webhook_logs');
    }
};
