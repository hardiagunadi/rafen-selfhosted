<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outages', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open');
            $table->string('severity', 20)->default('medium');
            $table->timestamp('started_at');
            $table->timestamp('estimated_resolved_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('public_token', 32)->unique();
            $table->timestamp('wa_blast_sent_at')->nullable();
            $table->unsignedInteger('wa_blast_count')->default(0);
            $table->timestamp('resolution_wa_sent_at')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('include_status_link')->default(true);
            $table->timestamps();

            $table->index(['status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outages');
    }
};
