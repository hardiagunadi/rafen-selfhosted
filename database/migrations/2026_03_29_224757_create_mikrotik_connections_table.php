<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mikrotik_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->unsignedSmallInteger('api_ssl_port')->default(8729);
            $table->boolean('use_ssl')->default(false);
            $table->string('username');
            $table->string('password');
            $table->string('radius_secret')->nullable();
            $table->string('ros_version')->default('auto');
            $table->unsignedTinyInteger('api_timeout')->default(10);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_online')->nullable();
            $table->unsignedInteger('last_ping_latency_ms')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->unsignedTinyInteger('failed_ping_count')->default(0);
            $table->boolean('ping_unstable')->default(false);
            $table->boolean('last_port_open')->nullable();
            $table->string('last_ping_message')->nullable();
            $table->unsignedSmallInteger('auth_port')->default(1812);
            $table->unsignedSmallInteger('acct_port')->default(1813);
            $table->string('timezone', 120)->default('+07:00 Asia/Jakarta');
            $table->string('isolir_url')->nullable();
            $table->boolean('isolir_setup_done')->default(false);
            $table->string('isolir_pool_name', 64)->nullable();
            $table->string('isolir_pool_range', 64)->nullable();
            $table->string('isolir_gateway')->nullable();
            $table->string('isolir_profile_name', 64)->nullable();
            $table->string('isolir_rate_limit', 32)->nullable();
            $table->timestamp('isolir_setup_at')->nullable();
            $table->string('hotspot_subnet')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_online']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_connections');
    }
};
