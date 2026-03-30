<?php

namespace Database\Factories;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SystemSetting>
 */
class SystemSettingFactory extends Factory
{
    protected $model = SystemSetting::class;

    public function definition(): array
    {
        return [
            'business_name' => 'Rafen Self-Hosted',
            'business_logo' => null,
            'business_phone' => '081234567890',
            'business_email' => 'admin@example.test',
            'website' => 'https://example.test',
            'business_address' => 'Jl. Raya ISP No. 1',
            'portal_title' => 'Portal Pelanggan',
            'portal_description' => 'Cek tagihan dan status layanan internet Anda.',
            'isolir_page_title' => 'Layanan Dinonaktifkan',
            'isolir_page_body' => 'Silakan selesaikan pembayaran untuk mengaktifkan layanan kembali.',
            'isolir_page_contact' => '081234567890',
            'isolir_page_bg_color' => '#1a1a2e',
            'isolir_page_accent_color' => '#e94560',
            'update_available_version' => null,
            'update_headline' => null,
            'update_summary' => null,
            'update_instructions' => null,
            'update_release_notes_url' => null,
            'update_severity' => null,
            'update_available_at' => null,
            'update_manual_only' => true,
            'update_is_active' => false,
        ];
    }
}
