<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppp_users', function (Blueprint $table) {
            $table->foreignId('odp_id')->nullable()->after('profile_group_id')->constrained('odps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ppp_users', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropColumn('odp_id');
        });
    }
};
