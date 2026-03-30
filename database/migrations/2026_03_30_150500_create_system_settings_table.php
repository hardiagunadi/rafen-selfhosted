<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name')->nullable();
            $table->string('business_logo')->nullable();
            $table->string('business_phone', 30)->nullable();
            $table->string('business_email')->nullable();
            $table->string('website')->nullable();
            $table->text('business_address')->nullable();
            $table->string('portal_title')->nullable();
            $table->text('portal_description')->nullable();
            $table->string('isolir_page_title')->nullable();
            $table->text('isolir_page_body')->nullable();
            $table->string('isolir_page_contact')->nullable();
            $table->string('isolir_page_bg_color', 20)->nullable();
            $table->string('isolir_page_accent_color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
