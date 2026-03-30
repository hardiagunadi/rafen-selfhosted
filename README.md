# Rafen Self-Hosted

Installer utama untuk fresh server ada di [install-selfhosted.sh](/var/www/rafen-selfhosted/install-selfhosted.sh).

## Fresh Server

Installer akan:
- meminta eskalasi `sudo` bila dijalankan dari user biasa
- menyiapkan `.env` dan direktori runtime
- menginstal dependency sistem dasar dan dependency aplikasi
- mengonfigurasi Nginx dan PHP-FPM
- menjalankan migrate, `storage:link`, dan bootstrap runtime aplikasi
- membuat super admin awal bila data admin diberikan

Contoh pakai untuk fresh server:

```bash
bash install-selfhosted.sh install \
  --domain billing.example.com \
  --license-public-key 'BASE64_PUBLIC_KEY_DARI_VENDOR' \
  --admin-name 'Super Admin' \
  --admin-email admin@example.com \
  --admin-password 'password-kuat'
```

Kalau tanpa domain:

```bash
bash install-selfhosted.sh install \
  --license-public-key 'BASE64_PUBLIC_KEY_DARI_VENDOR' \
  --admin-name 'Super Admin' \
  --admin-email admin@example.com \
  --admin-password 'password-kuat'
```

Jika `--domain` tidak diisi, installer akan fallback ke IP utama server untuk `APP_URL` dan konfigurasi Nginx.

## Opsi Penting

- `--domain <host>`: set domain/host publik
- `--app-url <url>`: override `APP_URL` penuh
- `--license-public-key <key>`: public key verifikasi lisensi, wajib diisi
- `--skip-system-bootstrap`: lewati provisioning package sistem dan Nginx/PHP-FPM
- `--wireguard-system`: siapkan helper WireGuard level sistem

## Cek Status

```bash
bash install-selfhosted.sh status
```
