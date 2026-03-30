<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotspot_users', function (Blueprint $table): void {
            $table->id();
            $table->string('status_registrasi', 20)->default('aktif');
            $table->string('tipe_pembayaran', 20)->default('prepaid');
            $table->string('status_bayar', 20)->default('belum_bayar');
            $table->string('status_akun', 20)->default('enable');
            $table->foreignId('hotspot_profile_id')->nullable()->constrained('hotspot_profiles')->nullOnDelete();
            $table->foreignId('profile_group_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('tagihkan_ppn')->default(false);
            $table->decimal('biaya_instalasi', 12, 2)->default(0);
            $table->date('jatuh_tempo')->nullable();
            $table->string('aksi_jatuh_tempo', 30)->default('isolir');
            $table->string('customer_id', 120)->nullable()->unique();
            $table->string('customer_name', 150);
            $table->string('nik', 50)->nullable();
            $table->string('nomor_hp', 30)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('alamat')->nullable();
            $table->string('username', 120)->unique();
            $table->string('metode_login', 50)->default('username_password');
            $table->string('hotspot_password', 120)->nullable();
            $table->text('catatan')->nullable();
            $table->string('mixradius_id', 120)->nullable()->index();
            $table->timestamps();

            $table->index(['status_akun', 'status_registrasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_users');
    }
};
