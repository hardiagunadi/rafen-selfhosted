<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_swap_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('from_schedule_id')->constrained('shift_schedules')->cascadeOnDelete();
            $table->foreignId('to_schedule_id')->nullable()->constrained('shift_schedules')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swap_requests');
    }
};
