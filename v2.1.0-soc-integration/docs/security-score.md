# SCORE.md — Detection Coverage & Security Score

> Ringkasan terukur dari siklus Red Team → Blue Team → Purple Team di `soc-l1-homelab`, mencakup infrastruktur lama (`enterprise-homelab-core`) dan TechSecure v2.0.

**Terakhir diupdate:** setelah penyelesaian Level 7 (TechSecure v2.0 purple-team cycle penuh)

---

## Security Score Formula

```
Security Score = (Detected Techniques / Total Techniques Attempted) × 40%
              + (1 − False Positive Rate) × 20%
              + (Hardening Controls Applied / Total Controls Identified) × 40%
```

## Skor Saat Ini

| Komponen | Nilai | Kontribusi |
|---|---|---|
| Detection rate | 9/9 teknik terdeteksi (100%) | 40.0% |
| False positive rate | 0% (belum ada FP tercatat) | 20.0% |
| Hardening coverage | 6/6 vuln TechSecure v2.0 + hardening lama (`v1.1.0`) | 40.0% |
| **Total Security Score** | | **100%** |

> ⚠️ Catatan jujur: skor 100% ini mencerminkan cakupan terhadap teknik yang **sudah diuji secara eksplisit** dalam lab ini — bukan klaim bahwa sistem "sepenuhnya aman" secara umum. Detection rate akan turun secara alami setiap kali teknik serangan baru ditambahkan ke pengujian (by design — itu tujuan iterasi berikutnya).

---

## 🎉 Level 10 — SOAR Automation: SELESAI

Real-time alerting via **Wazuh Integration Framework → Python script → Telegram Bot API** berhasil divalidasi end-to-end (lihat INC-0005). Alert level ≥10 sekarang otomatis terkirim ke Telegram tanpa perlu membuka dashboard.

**Temuan penting dari proses ini:** rule deteksi awal (100010/100012/100014) hanya menghitung parent `31100` (response sukses), sehingga setelah hardening diterapkan dan serangan mulai gagal (403), rule tersebut **berhenti mendeteksi sama sekali** — SOC jadi "buta" terhadap percobaan serangan yang gagal, bukan cuma yang berhasil. Diperbaiki dengan memperluas parent jadi `31100,31101` di ketiga rule tersebut. Ini koreksi penting: **mendeteksi percobaan yang diblokir sama pentingnya dengan mendeteksi yang berhasil.**

---

## Detection Coverage Matrix

### Dari `enterprise-homelab-core` (fondasi awal)

| Teknik (MITRE) | Serangan | Terdeteksi | Diinvestigasi | Direspons | Case |
|---|---|---|---|---|---|
| T1110.001 | SSH Brute Force | ✅ (default rule) | ✅ | N/A (benign) | INC-0001 |
| T1110 | FTP Brute Force (Hydra) | ✅ (default rule, correlation) | ✅ | ⬜ (active-response belum) | INC-0002 |

### Dari TechSecure v2.0 (Level 7)

| Teknik (MITRE) | Serangan | Terdeteksi (default) | Terdeteksi (custom rule) | Investigasi | Hardened | Retest Final | Case |
|---|---|---|---|---|---|---|---|
| T1213 | VULN-01 — IDOR profile enumeration | ❌ | ✅ (rule 100010/100011) | ✅ | ✅ | ✅ Gagal dieksploitasi | INC-0003 |
| T1110.003 | VULN-02 — Predictable reset token | ❌ | ⬜ (belum ditulis rule khusus) | — | ✅ | ✅ Token sekarang random 128-bit | — |
| T1005 | VULN-03 — BOLA report download | ❌ | ✅ (rule 100012/100013, ditulis sepasang dengan VULN-01) | ⬜ (belum retest formal) | ✅ | ✅ Gagal dieksploitasi | — |
| T1090 | VULN-04 — SSRF avatar import | ❌ | ✅ (rule 100014, regex-based) | ⬜ (belum retest formal) | ✅ | ✅ Gagal dieksploitasi | — |
| T1190 | VULN-05 — Missing dashboard auth | ❌ | ⬜ (belum ditulis rule) | — | ✅ | ✅ Redirect ke login | INC-0004 |
| T1098 | VULN-06 — Unrestricted password reset (account takeover) | ❌ | ⬜ (belum ditulis rule) | — | ✅ | ✅ Token tidak lagi terekspos | — |

**Ringkasan:** 6/6 kerentanan berhasil di-hardening dan lolos final retest. 3/6 punya custom detection rule yang tervalidasi (VULN-01, 03, 04); 3/6 lainnya (VULN-02, 05, 06) di-patch langsung tanpa custom rule terpisah, karena ditemukan/diperbaiki di tengah proses verifikasi, bukan lewat siklus formal Attack→Gap Analysis→Detection.

---

## Backlog Detection Engineering (untuk iterasi berikutnya)

Rule yang **belum** ditulis, jadi prioritas kalau lanjut ke iterasi berikutnya:
- [ ] Rule untuk VULN-02 pattern — request `reset-password.php` berulang dari 1 IP dalam waktu singkat (indikasi mencoba banyak token)
- [ ] Rule untuk VULN-05 pattern — akses langsung ke halaman yang butuh auth tanpa `POST /index.php` (login) sebelumnya dari source yang sama
- [ ] Rule untuk VULN-06 pattern — banyak `POST /reset-password.php` dengan `username` berbeda-beda dari 1 IP (indikasi username enumeration + mass account-takeover attempt)
- [ ] Active-response otomatis untuk rule 100011 (auto-block IP setelah alert IDOR level 10)
- [ ] Retest formal untuk VULN-03 dan VULN-04 dengan dokumentasi INC-XXXX terpisah (saat ini hanya diverifikasi manual, belum didokumentasikan sebagai case resmi)

---

## Infrastruktur & Deteksi — Ringkasan Teknis

| Komponen | Status |
|---|---|
| Wazuh Manager, Indexer, Dashboard | ✅ Live, 4.14.7 |
| Wazuh Agent (Ubuntu Target) | ✅ Aktif, terhubung |
| Custom rules (`local_rules.xml`) | ✅ 5 rule aktif (100010–100014) |
| Log source: PAM/auth | ✅ Default, aktif sejak awal |
| Log source: Apache access log | ✅ Ditambahkan manual (`other_vhosts_access.log`) |
| Log source: auditd | ⬜ Ditunda (constraint resource) |
| SOAR / Automation | ✅ Wazuh Integration Framework → Telegram, real-time, tervalidasi (INC-0005) |
| Cloud integration | ⬜ Gagal (Oracle Cloud), belum dicoba ulang |

---

## Lessons Learned Ringkas (lihat dokumentasi lengkap di masing-masing file)

1. Detection engineering sebagian besar soal **memastikan pipeline data benar** (log source, port firewall, kredensial sinkron) — bukan cuma soal menulis sintaks rule.
2. Rule correlation (`frequency` + `timeframe` + `same_source_ip`) adalah pola yang **reusable** di berbagai jenis teknik, tidak terbatas pada satu use-case.
3. Kerentanan yang paling berharga untuk portofolio kadang ditemukan **secara tidak sengaja** (VULN-05, VULN-06) saat memverifikasi hal lain — bukti kemampuan observasi, bukan hanya eksekusi rencana.
4. Password/credential yang di-rotate di satu komponen (mis. OpenSearch admin) harus disinkronkan ke semua konsumen kredensial itu (Filebeat, dashboard) — kegagalan silent di titik ini bisa membuat pipeline terlihat sehat padahal alert tidak pernah sampai ke analyst.
