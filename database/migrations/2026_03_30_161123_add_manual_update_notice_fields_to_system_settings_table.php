<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->string('update_available_version')->nullable()->after('isolir_page_accent_color');
            $table->string('update_headline')->nullable()->after('update_available_version');
            $table->text('update_summary')->nullable()->after('update_headline');
            $table->text('update_instructions')->nullable()->after('update_summary');
            $table->string('update_release_notes_url')->nullable()->after('update_instructions');
            $table->string('update_severity', 20)->nullable()->after('update_release_notes_url');
            $table->timestamp('update_available_at')->nullable()->after('update_severity');
            $table->boolean('update_manual_only')->default(true)->after('update_available_at');
            $table->boolean('update_is_active')->default(false)->after('update_manual_only');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'update_available_version',
                'update_headline',
                'update_summary',
                'update_instructions',
                'update_release_notes_url',
                'update_severity',
                'update_available_at',
                'update_manual_only',
                'update_is_active',
            ]);
        });
    }
};
