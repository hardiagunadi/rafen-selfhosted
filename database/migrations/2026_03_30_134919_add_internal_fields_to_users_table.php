<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)->default('administrator')->after('password');
            $table->string('phone', 30)->nullable()->after('role');
            $table->string('nickname', 60)->nullable()->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_super_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'role',
                'phone',
                'nickname',
                'last_login_at',
            ]);
        });
    }
};
