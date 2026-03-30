<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->decimal('harga_jual', 12, 2)->default(0);
            $table->decimal('harga_promo', 12, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(0);
            $table->foreignId('bandwidth_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('profile_type', 20)->default('unlimited');
            $table->string('limit_type', 20)->nullable();
            $table->unsignedInteger('time_limit_value')->nullable();
            $table->string('time_limit_unit', 20)->nullable();
            $table->decimal('quota_limit_value', 12, 2)->nullable();
            $table->string('quota_limit_unit', 10)->nullable();
            $table->unsignedInteger('masa_aktif_value')->nullable();
            $table->string('masa_aktif_unit', 20)->nullable();
            $table->foreignId('profile_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parent_queue', 200)->nullable();
            $table->unsignedInteger('shared_users')->default(1);
            $table->string('prioritas', 20)->default('default');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_profiles');
    }
};
