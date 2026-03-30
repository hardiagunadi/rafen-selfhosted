<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('mikrotik_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('pppoe');
            $table->string('ip_pool_mode', 20)->default('group_only');
            $table->string('ip_pool_name', 120)->nullable();
            $table->string('ip_address', 120)->nullable();
            $table->string('netmask', 120)->nullable();
            $table->string('range_start', 120)->nullable();
            $table->string('range_end', 120)->nullable();
            $table->string('dns_servers', 191)->nullable();
            $table->string('parent_queue', 120)->nullable();
            $table->string('host_min', 120)->nullable();
            $table->string('host_max', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_groups');
    }
};
