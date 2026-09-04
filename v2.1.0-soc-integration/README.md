# SOC L1 Homelab

Lanjutan dari [`enterprise-homelab-core`](../enterprise-homelab-core/): membangun kapabilitas SOC L1 penuh di atas infrastruktur yang sudah ada — visibility, deteksi, respons insiden, red/blue/purple team, dan automation.

## Latar Belakang

Setelah siklus hardening pertama pada `enterprise-homelab-core` selesai (lihat riwayat lengkap di `enterprise-homelab-core/v1.1.0-hardened.md`), tim pengembang PT TechSecure Indonesia berencana merilis pembaruan besar berikutnya: **TechSecure v2.0**. Dua fitur baru masuk dalam rencana rilis — self-service password reset (mengurangi beban tiket IT support) dan REST API publik untuk kebutuhan integrasi dengan sistem mitra.

Mengingat riwayat insiden pada versi sebelumnya, manajemen meminta agar pembaruan ini tidak langsung dirilis tanpa peninjauan keamanan terlebih dahulu — pertanyaan yang diajukan sederhana: *apakah fitur baru ini membuka celah baru, dan apakah tim sekarang punya visibility untuk mendeteksinya kalau ada yang mencoba mengeksploitasi?*

Dokumentasi di repository ini adalah catatan lengkap dari proses menjawab pertanyaan itu — mulai dari membangun visibility (SIEM) yang sebelumnya tidak ada, melakukan pengujian penetrasi internal terhadap build v2.0 sebelum rilis, sampai memperbaiki dan memvalidasi ulang setiap temuan.

## Alur Kerja

1. **Membangun visibility terlebih dahulu.** Sebelum pengujian apa pun dilakukan, infrastruktur SIEM (Wazuh) dipasang dan divalidasi — supaya setiap upaya eksploitasi selama pengujian bisa diamati, bukan cuma dilakukan lalu dilupakan.
2. **Pengujian penetrasi terhadap build v2.0.** Dilakukan sebagai engagement terpisah, didokumentasikan lengkap di [`red-team-arsenal/`](red-team-arsenal/) — mencakup metodologi, ruang lingkup, dan seluruh script yang digunakan.
3. **Analisis kesenjangan deteksi.** Setiap temuan pengujian dicek ulang terhadap SIEM: apakah aktivitas itu tercatat sebagai alert, atau lolos begitu saja tanpa terdeteksi?
4. **Detection engineering.** Untuk celah yang tidak tertangkap ruleset bawaan, rule deteksi baru ditulis dari nol dan divalidasi (lihat [`detection-engineering/`](detection-engineering/)).
5. **Remediasi.** Kode aplikasi diperbaiki berdasarkan temuan (lihat [`application/`](application/)).
6. **Retest.** Setiap skenario serangan yang sama diulang persis setelah perbaikan diterapkan, untuk membuktikan bahwa upaya eksploitasi sekarang gagal — dan tetap tercatat oleh SIEM sebagai percobaan yang diblokir, bukan menghilang begitu saja dari radar.
7. **Automation respons.** Sebagai tahap lanjutan, alert dengan tingkat keparahan tinggi dihubungkan ke notifikasi real-time (lihat [`automation/`](automation/)), supaya analis tidak harus terus-menerus memantau dashboard secara manual.

Rincian naratif tiap temuan — termasuk proses debugging saat sesuatu tidak berjalan sesuai rencana — ada di [`incident-reports/`](incident-reports/).

## Struktur

```
.
├── docs/                    Roadmap, catatan setup & troubleshooting, desain kerentanan
├── incident-reports/        Laporan investigasi tiap kejadian yang ditemukan/disimulasikan
├── application/             Source code aplikasi target (versi setelah hardening)
├── red-team-arsenal/        Engagement pengujian penetrasi — metodologi & script serangan
├── detection-engineering/   Custom Wazuh detection rules
├── automation/              Integrasi SOAR (Wazuh → Telegram)
└── media/                   Dokumentasi visual (screenshot)
```

## Arsitektur

```
MikroTik (edge) ── Ubuntu Server (target aplikasi) ── Kali Linux (red team)
                              │
                         Wazuh Agent
                              │
              Wazuh Manager + Indexer + Dashboard
                              │
                  Wazuh Integration Framework
                              │
                     Telegram (notifikasi real-time)
```

## Menjalankan Ulang

- Setup infrastruktur SIEM: `docs/siem-setup-and-troubleshooting.md`
- Deploy aplikasi target: `application/DEPLOY.md`
- Skor keamanan & cakupan deteksi terkini: `docs/security-score.md`

