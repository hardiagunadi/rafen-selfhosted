<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private const TOPICS = [
        'pppoe' => [
            'title' => 'PPPoE dan Pelanggan',
            'summary' => 'Kelola paket PPP, pelanggan, profil bandwidth, dan alur aktivasi layanan single-tenant.',
            'highlights' => [
                'Buat profile group, bandwidth profile, dan paket PPP sebelum menambahkan pelanggan.',
                'Setiap pelanggan menyimpan status akun, status bayar, jatuh tempo, dan kredensial portal.',
                'Invoice dapat dibuat langsung dari pelanggan PPP yang sudah aktif.',
            ],
        ],
        'hotspot' => [
            'title' => 'Hotspot dan Voucher Internal',
            'summary' => 'Struktur paket hotspot self-hosted mengikuti pola global tanpa owner atau tenant scoping.',
            'highlights' => [
                'Paket hotspot dan pelanggan hotspot dikelola dari panel super admin yang sama.',
                'Username dapat otomatis disamakan dengan password untuk skenario captive portal sederhana.',
                'Profile group tetap bisa dipakai untuk menstandarkan jaringan dan paket.',
            ],
        ],
        'invoice' => [
            'title' => 'Invoice dan Pembayaran',
            'summary' => 'Billing self-hosted difokuskan ke invoice internal dan pembayaran manual tanpa payment gateway platform.',
            'highlights' => [
                'Invoice dibentuk dari data paket pelanggan yang sudah terporting.',
                'Pembayaran manual akan menandai invoice lunas dan mengaktifkan kembali status layanan pelanggan.',
                'Ekspor transaksi CSV tersedia dari System Tools untuk kebutuhan audit dan rekonsiliasi.',
            ],
        ],
        'portal' => [
            'title' => 'Portal Pelanggan',
            'summary' => 'Pelanggan bisa login menggunakan customer ID, username, atau nomor HP untuk melihat tagihan dan akun.',
            'highlights' => [
                'Portal memakai sesi sendiri yang terpisah dari auth admin.',
                'Password portal mendukung format lama plain text dan hash baru.',
                'Pelanggan dapat mengganti password portal dari halaman akun.',
            ],
        ],
        'gangguan' => [
            'title' => 'Gangguan dan Status Publik',
            'summary' => 'Insiden jaringan dapat dipublikasikan ke halaman status publik dengan update progres bertahap.',
            'highlights' => [
                'Area terdampak disimpan sebagai label bebas agar tetap ringan di mode self-hosted.',
                'Setiap gangguan bisa memiliki token publik untuk dibagikan ke pelanggan.',
                'Timeline update internal dan publik dipisahkan melalui flag update pada data gangguan.',
            ],
        ],
        'cpe-genieacs' => [
            'title' => 'CPE dan GenieACS',
            'summary' => 'Integrasi TR-069 difokuskan ke inventaris perangkat, parameter penting, dan sinkronisasi yang tahan terhadap struktur index GenieACS.',
            'highlights' => [
                'Pembacaan parameter dibuat toleran terhadap struktur path parameter yang tidak seragam.',
                'Data perangkat dapat dipakai untuk membantu proses operasional dan dukungan pelanggan.',
                'Self-hosted tetap mengandalkan konfigurasi GenieACS dan CPE yang terpusat di panel admin.',
            ],
        ],
        'system-tools' => [
            'title' => 'System Tools dan Audit',
            'summary' => 'Self-hosted menyediakan backup snapshot, restore, export transaksi, dan activity log untuk satu instalasi.',
            'highlights' => [
                'Backup disimpan sebagai snapshot terkompresi berformat json.gz pada storage lokal.',
                'Activity log merekam aksi penting seperti backup, restore, dan export transaksi.',
                'Restore snapshot dirancang tetap berjalan di sqlite dan database server umum.',
            ],
        ],
        'whatsapp-gateway' => [
            'title' => 'WhatsApp Gateway',
            'summary' => 'Modul WhatsApp di self-hosted saat ini masih berfokus pada konfigurasi gateway dan fondasi ticket publik.',
            'highlights' => [
                'Pengaturan gateway tetap dikelola global untuk satu instalasi.',
                'Fondasi tiket publik sudah tersedia untuk dibangun di atas modul WA operasional berikutnya.',
                'Halaman progres tiket publik dapat dibagikan menggunakan token unik tanpa login pelanggan.',
            ],
        ],
    ];

    public function index(): View
    {
        return view('help.index', [
            'topics' => self::TOPICS,
        ]);
    }

    public function topic(string $slug): View
    {
        abort_unless(isset(self::TOPICS[$slug]), 404);

        return view('help.topic', [
            'slug' => $slug,
            'topic' => self::TOPICS[$slug],
        ]);
    }
}
