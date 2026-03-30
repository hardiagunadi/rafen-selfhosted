<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppp_users', function (Blueprint $table): void {
            $table->id();
            $table->string('status_registrasi', 20)->default('aktif');
            $table->string('tipe_pembayaran', 20)->default('prepaid');
            $table->string('status_bayar', 20)->default('belum_bayar');
            $table->string('status_akun', 20)->default('enable');
            $table->foreignId('ppp_profile_id')->constrained('ppp_profiles')->cascadeOnDelete();
            $table->string('tipe_service', 20)->default('pppoe');
            $table->boolean('tagihkan_ppn')->default(true);
            $table->boolean('prorata_otomatis')->default(false);
            $table->boolean('promo_aktif')->default(false);
            $table->unsignedInteger('durasi_promo_bulan')->default(0);
            $table->decimal('biaya_instalasi', 12, 2)->default(0);
            $table->date('jatuh_tempo')->nullable();
            $table->string('aksi_jatuh_tempo', 30)->default('isolir');
            $table->string('tipe_ip', 20)->default('dhcp');
            $table->foreignId('profile_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_static', 120)->nullable();
            $table->string('odp_pop', 120)->nullable();
            $table->string('customer_id', 120)->nullable()->unique();
            $table->string('customer_name', 150);
            $table->string('nik', 191)->nullable();
            $table->string('nomor_hp', 30)->unique();
            $table->string('email', 191)->nullable();
            $table->text('alamat')->nullable();
            $table->string('latitude', 120)->nullable();
            $table->string('longitude', 120)->nullable();
            $table->decimal('location_accuracy_m', 8, 2)->nullable();
            $table->string('location_capture_method', 30)->nullable();
            $table->timestamp('location_captured_at')->nullable();
            $table->string('metode_login', 40)->default('username_password');
            $table->string('username', 120)->unique();
            $table->string('ppp_password', 120)->nullable();
            $table->string('password_clientarea', 120)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['status_akun', 'status_registrasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppp_users');
    }
};
