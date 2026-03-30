<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outage_affected_areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outage_id')->constrained()->cascadeOnDelete();
            $table->string('area_type', 30)->default('keyword');
            $table->string('label', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_affected_areas');
    }
};
