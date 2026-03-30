<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppp_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->decimal('harga_modal', 12, 2)->default(0);
            $table->decimal('harga_promo', 12, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(0);
            $table->foreignId('profile_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bandwidth_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parent_queue', 200)->nullable();
            $table->unsignedInteger('masa_aktif')->default(1);
            $table->string('satuan', 20)->default('bulan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppp_profiles');
    }
};
