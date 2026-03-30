<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('role', 32)->nullable();
            $table->string('color', 16)->default('#3b82f6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_definitions');
    }
};
