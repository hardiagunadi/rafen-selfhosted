<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bandwidth_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->unsignedInteger('upload_min_mbps')->default(0);
            $table->unsignedInteger('upload_max_mbps')->default(0);
            $table->unsignedInteger('download_min_mbps')->default(0);
            $table->unsignedInteger('download_max_mbps')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandwidth_profiles');
    }
};
