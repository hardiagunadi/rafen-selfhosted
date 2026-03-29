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
        Schema::create('radius_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radius_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username');
            $table->string('attribute');
            $table->string('op', 4)->default(':=');
            $table->text('value');
            $table->timestamps();

            $table->unique(['username', 'attribute']);
            $table->index(['radius_account_id', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radius_replies');
    }
};
