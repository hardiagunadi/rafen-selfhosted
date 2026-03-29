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
        Schema::create('radius_nas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname', 120)->unique();
            $table->string('ip_address', 45);
            $table->string('secret');
            $table->boolean('require_message_authenticator')->default(true);
            $table->unsignedSmallInteger('auth_port')->default(1812);
            $table->unsignedSmallInteger('acct_port')->default(1813);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radius_nas');
    }
};
