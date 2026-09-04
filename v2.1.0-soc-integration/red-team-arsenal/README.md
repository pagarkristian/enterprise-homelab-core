# Penetration Test Engagement — TechSecure v2.0 Pre-Release Assessment

## Latar Belakang

Sebelum TechSecure v2.0 dirilis ke lingkungan produksi, manajemen meminta agar dua fitur baru — self-service password reset dan REST API publik — ditinjau keamanannya terlebih dahulu. Engagement ini dijalankan sebagai pengujian penetrasi internal (black-box dari sisi aplikasi, dengan akses kredensial pengguna biasa) terhadap build v2.0 sebelum disetujui untuk rilis.

## Ruang Lingkup

| Item | Detail |
|---|---|
| Target | Aplikasi web TechSecure v2.0, host `10.216.27.100` |
| Akses awal | Kredensial pengguna biasa (bukan admin) |
| Di luar lingkup | Infrastruktur jaringan (MikroTik), sistem operasi host |
| Metodologi | Manual testing pada endpoint baru (API, reset password), verifikasi hardening lama (SSH/FTP) masih berlaku |

## Metodologi

Pengujian dilakukan mengikuti siklus standar: **temukan celah → eksploitasi untuk membuktikan dampak nyata → laporkan ke tim development → tunggu perbaikan diterapkan → uji ulang persis skenario yang sama untuk memvalidasi perbaikan**. Setiap script di folder ini merepresentasikan satu skenario serangan yang dipakai berulang kali sepanjang siklus tersebut — pertama untuk membuktikan celah ada, dan sekali lagi setelah perbaikan untuk memastikan celah sudah tertutup.

## Temuan

| # | Kerentanan | Endpoint | Tingkat Keparahan | Status Akhir |
|---|---|---|---|---|
| 1 | Broken Object Level Authorization — profil pengguna dapat diakses lintas akun | `api/v1/users/profile.php` | Sedang | Diperbaiki, divalidasi |
| 2 | Token reset password dapat ditebak (berbasis timestamp) | `reset-password.php` | Sedang | Diperbaiki, divalidasi |
| 3 | Broken Object Level Authorization — laporan/dokumen lintas akun dapat diunduh | `api/v1/reports/download.php` | Tinggi | Diperbaiki, divalidasi |
| 4 | Server-Side Request Forgery pada fitur impor avatar | `profile/avatar-from-url.php` | Tinggi | Diperbaiki, divalidasi |
| 5 | Halaman dashboard dapat diakses tanpa autentikasi | `dashboard.php` | Sedang | Ditemukan saat verifikasi temuan #1, diperbaiki |
| 6 | Reset password dapat dipicu untuk akun siapa pun tanpa verifikasi kepemilikan | `reset-password.php` | Tinggi | Ditemukan saat verifikasi temuan #2, diperbaiki |

Detail teknis lengkap tiap temuan (payload, root cause, bukti eksploitasi, rekomendasi perbaikan) ada di `../docs/vulnerability-design.md`. Narasi proses investigasi dan perbaikan ada di `../incident-reports/`.

## Script Pengujian

| Script | Kerentanan target | Teknik MITRE |
|---|---|---|
| `idor_enumeration.sh` | Temuan #1 — profile API | T1213 |
| `bola_report_enumeration.sh` | Temuan #3 — report download | T1005 |
| `ftp_bruteforce.sh` | Baseline — memverifikasi hardening kredensial dari siklus sebelumnya (`enterprise-homelab-core`) masih berlaku | T1110 |
| `ssrf_probe.sh` | Temuan #4 — avatar import | T1090 |

Setiap script mengasumsikan target berada di jaringan lab terisolasi (`10.216.27.0/24`). Sesuaikan variabel `TARGET_HOST` bila menjalankan ulang di environment berbeda.

## Retest

Setelah tim development menerapkan perbaikan (lihat `../application/`), seluruh script di atas dijalankan ulang tanpa modifikasi terhadap target yang sudah diperbarui. Hasil retest: seluruh percobaan eksploitasi gagal (ditolak dengan kode 403 atau pesan validasi yang sesuai), dan — setelah perbaikan pada sisi deteksi (lihat `../detection-engineering/`) — percobaan yang gagal tersebut tetap tercatat sebagai alert di SIEM, bukan menghilang tanpa jejak.

## Catatan Penggunaan

Script-script ini dibuat khusus untuk lab pribadi yang terisolasi dari internet. Jangan jalankan terhadap sistem yang tidak dimiliki atau tidak memiliki izin eksplisit untuk diuji.
