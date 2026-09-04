# 🎯 Project: TechSecure SOC — From Weakest Server to SOC L1 Portfolio

> **Concept:** "Weakest Server → Strongest Server" — mengubah `enterprise-homelab-core` yang sudah ada menjadi miniature enterprise security environment lengkap dengan Red Team, Blue Team, SOC, Purple Team, dan Security Automation, dengan fokus utama menunjukkan kompetensi **SOC L1**.

---

## A. Project Vision

Kamu sudah punya fondasi yang kuat: `enterprise-homelab-core` (infrastruktur + hardening cycle) dan `redteam-homelab-toolkit` (offensive playbook). Project ini **tidak dimulai dari nol** — ini adalah **upgrade** dari apa yang sudah kamu bangun, dengan menambahkan lapisan yang selama ini hilang: **visibility, detection, dan SOC workflow**.

Target akhir: portofolio yang membuktikan kamu paham *full loop* —

```
Attack → Telemetry → Detection → Alert → SOC L1 Triage → Investigation →
Evidence → IOC Analysis → MITRE Mapping → Severity → TP/FP → Escalation →
Blue Team Remediation → Detection Improvement → Retest
```

Bukan sekadar kumpulan tools, tapi bukti kamu mengerti **kenapa** tiap komponen ada di sana.

---

## B–C. Project & Network Architecture (Full Lokal — RAM 8GB, VirtualBox)

**Update:** Oracle Cloud Free Tier gagal di-setup (verifikasi kartu ditolak berulang). Project ini dijalankan **100% lokal** di VirtualBox. Ini lebih ketat di RAM, jadi strateginya: **jangan nyalakan semua VM bersamaan** — nyalakan sesuai fase yang sedang dikerjakan.

### Daftar VM (network `10.216.27.0/24` yang sudah ada)
| VM | Role | RAM alokasi | Kapan dinyalakan |
|---|---|---|---|
| MikroTik CHR | Edge router/firewall (sudah ada) | 128 MB | Selalu (ringan) |
| **SOC-Manager (baru)** | Wazuh Manager + Indexer + Dashboard (tuned ringan) | 3.5–4 GB | Saat kerja Level 2 ke atas |
| Ubuntu Target | TechSecure portal + Wazuh **agent** (sudah ada, extend) | 1 GB | Saat butuh generate/deteksi attack |
| Kali Linux | Attacker node (sudah ada) | 1.5–2 GB | Saat simulasi Red Team saja |
| Windows 10/11 (baru, minimal install) | Endpoint + Sysmon + Wazuh agent | 2 GB | Saat kerja Windows telemetry saja |

**Aturan main:** host Windows 11 kamu sendiri makan ±3GB. Jadi kombinasi VM yang realistis nyala bersamaan cuma **2 VM sekaligus** (misal SOC-Manager + Ubuntu Target saat testing detection, atau SOC-Manager + Kali saat serangan). Simpan snapshot tiap VM biar bisa on/off cepat tanpa kehilangan state.

**Tuning wajib biar Wazuh gak makan RAM berlebih:**
- Set OpenSearch/indexer JVM heap ke `1g` (bukan default yang bisa minta lebih), edit `/etc/wazuh-indexer/jvm.options` → `-Xms1g -Xmx1g`
- Matikan service yang gak perlu di VM lain saat SOC-Manager jalan
- Kalau kerasa berat, pertimbangkan upgrade RAM laptop ke 16GB — ini investasi kecil yang bakal sangat membantu untuk project jangka panjang ini

### (Dihapus dari rencana) Komponen cloud
TheHive, Shuffle/n8n SOAR, dan honeypot cloud (Level 10 & 13) untuk sementara **ditunda** — akan dievaluasi ulang setelah Level 2–9 solid secara lokal. Kalau nanti RAM sudah upgrade atau cloud provider lain berhasil di-setup, bagian ini bisa diaktifkan kembali.

---

## D. VM Architecture

Extend dari yang sudah ada — **jangan bongkar `v1.1.0-hardened`**, itu tetap jadi baseline "protected environment". Tambahkan:
- 1x Windows endpoint (Sysmon + auditing) → representasi AD-lite / workstation korban
- (Opsional Level 13+) 1x Windows Server sebagai DC kalau RAM sudah di-upgrade

---

## E–H. Security, SIEM & Detection Architecture

- **Log sources:** Wazuh agent (Ubuntu target), Sysmon + Windows Event Log (Windows endpoint), MikroTik firewall log (syslog ke Wazuh)
- **Detection-as-Code:** semua custom rule Wazuh (`local_rules.xml`) di-commit ke Git, bukan diketik manual di server — ini yang membedakan portofolio kamu dari tutorial follower biasa
- **MITRE ATT&CK mapping:** setiap rule custom wajib punya tag `<mitre><id>Txxxx</id></mitre>`, contoh persis yang sudah kamu punya di REFERENSI.docx (T1110 brute force)

---

## I–L. Red / Blue / SOC / Purple Team Scope

- **Red Team:** reuse & extend `redteam-homelab-toolkit` — setiap exploit lama (SQLi, unrestricted upload, FTP anon, SSH brute) dijalankan ulang, tapi sekarang **tujuannya menghasilkan telemetry**, bukan cuma exploit
- **Blue Team:** lanjutan `v1.1.0-hardened` — iterasi berikutnya berdasarkan detection gap yang ditemukan
- **SOC (fokus utama):** kamu berperan sebagai analyst yang menerima alert dari Wazuh, triage, buat case di TheHive, tentukan TP/FP
- **Purple Team:** setelah tiap detection rule dibuat, retest attack yang sama — apakah sekarang terdeteksi lebih cepat?

---

## M–N. Automation & Optional AI Integration

- SOAR ringan (Shuffle/n8n) untuk: enrichment IP/hash ke VirusTotal, notifikasi Telegram, auto-block via Wazuh Active Response — persis workflow di REFERENSI.docx tapi kamu bangun sendiri step-by-step, didokumentasikan sebagai bukti pemahaman, bukan copy-paste
- **AI-assisted SOC (Level 14, opsional/stretch):** karena kamu sudah terbiasa AI-assisted automation, ini bisa jadi diferensiator kuat — bikin script kecil pakai Claude API yang meringkas alert Wazuh jadi draft triage note. Ini realistis dan relevan untuk 2026, bukan gimmick.

---

## O–Q. Case Structure & Detection Coverage

Setiap serangan yang berhasil dideteksi → dicatat sebagai case, format persis seperti yang kamu rancang sendiri:

```
INC-0001 — Multiple Failed SSH Authentication
Timestamp | Host | User | Source IP | Destination | Process |
Command Line | Evidence | IOC | Severity | MITRE ATT&CK |
Investigation | Timeline | Verdict | Recommended Action |
Escalation Decision | Final Status
```

**Detection Coverage Matrix** (update tiap level baru):

| Technique (MITRE) | Attack | Detected | Investigated | Responded |
|---|---|---|---|---|
| T1110 Brute Force | SSH hydra | ✅ | ✅ | ✅ (auto-block) |
| T1190 Exploit Public-Facing App | SQLi login bypass | ⬜ | ⬜ | ⬜ |
| T1105 Ingress Tool Transfer | Unrestricted upload → webshell | ⬜ | ⬜ | ⬜ |
| ... | | | | |

---

## S. Security Scoring System (formula sederhana & jelas)

```
Security Score = (Detected Techniques / Total Techniques Attempted) × 40%
              + (1 − False Positive Rate) × 20%
              + (Hardening Controls Applied / Total Controls Identified) × 40%
```
Dihitung ulang tiap akhir level, dicatat di `SCORE.md` di repo — ini yang bikin progress kamu **measurable**, bukan sekadar klaim.

---

## R. Repository Structure

```
soc-l1-homelab/               ← repo baru, cross-link ke enterprise-homelab-core & redteam-homelab-toolkit
├── README.md                  ← vision, architecture diagram, progress score
├── docs/
│   ├── architecture/
│   ├── detection-coverage.md
│   └── score-history.md
├── detections/                ← Wazuh local_rules.xml, version-controlled
├── cases/                     ← INC-XXXX.md per incident
├── automation/                ← SOAR workflow exports, scripts
├── scripts/                   ← red team scripts (link/reuse dari redteam-homelab-toolkit)
└── SCORE.md
```

---

## U–Y. Milestones, Level Progression & Estimasi

Karena kamu sudah punya Level 1 (infrastruktur) dan sudah cukup familiar SIEM, kita mulai efektif dari Level 2. Dengan 20+ jam/minggu, tiap level realistis selesai 1–2 minggu.

### Level 1 — Basic Infrastructure ✅ (sudah selesai — enterprise-homelab-core)
Skip, sudah jadi fondasi.

### Level 2 — Logging
- **Objective:** semua host lokal mengirim log terpusat
- **Tech:** Wazuh agent (Ubuntu), Sysmon + sysmon-modular config (Windows), syslog MikroTik
- **Task:** install agent, verifikasi event masuk ke manager cloud
- **Evidence:** screenshot agent status, sample raw log tiap sumber
- **Estimasi:** 8–12 jam | **Difficulty:** Easy | **SOC L1 relevance:** Tinggi (log source awareness)

### Level 3 — SIEM
- **Objective:** Wazuh manager di Oracle Cloud Free Tier menerima & mengindeks semua log
- **Task:** deploy Wazuh (single-node OSS), setup dashboard, hubungkan agent lokal via internet/VPN
- **Evidence:** dashboard screenshot, arsitektur diagram log flow
- **Estimasi:** 10–15 jam | **Difficulty:** Medium

### Level 4 — Detection
- **Objective:** custom detection rule untuk minimal 3 teknik MITRE (brute force, exploit public app, suspicious upload)
- **Task:** tulis `local_rules.xml`, commit ke Git, test trigger
- **Evidence:** rule file + alert screenshot + entry di Detection Coverage Matrix
- **Estimasi:** 12–18 jam | **Difficulty:** Medium | **Portfolio value:** Tinggi

### Level 5 — SOC L1 Triage
- **Objective:** setup TheHive, buat alur triage manual: alert → verifikasi → klasifikasi severity → TP/FP
- **Task:** buat minimal 5 case (INC-0001 s/d INC-0005) dari serangan Level 4
- **Evidence:** case files lengkap di repo
- **Estimasi:** 10 jam | **Difficulty:** Easy-Medium | **SOC L1 relevance:** Sangat tinggi (ini inti kompetensi yang dicari)

### Level 6 — Investigation
- **Objective:** perkaya case dengan IOC enrichment (VirusTotal), timeline reconstruction
- **Task:** integrasikan VirusTotal API manual dulu (belum otomatis)
- **Evidence:** update tiap case dengan section Investigation & IOC
- **Estimasi:** 8 jam | **Difficulty:** Medium

### Level 7 — Red Team Simulation
- **Objective:** reuse script dari `redteam-homelab-toolkit`, jalankan ulang serangan lama sebagai trigger telemetry terstruktur
- **Task:** dokumentasikan tiap serangan sebagai "atomic test" dengan expected detection
- **Estimasi:** 10 jam | **Difficulty:** Easy (kamu sudah kuat di sini)

### Level 8 — Blue Team Hardening (iterasi lanjutan)
- **Objective:** tutup detection gap yang ditemukan Level 4–7
- **Estimasi:** 8–12 jam | **Difficulty:** Medium

### Level 9 — Purple Team Validation
- **Objective:** retest semua attack, ukur peningkatan Detection Coverage & Security Score
- **Evidence:** update `SCORE.md` — Iteration 0 vs Iteration 1
- **Estimasi:** 6 jam | **Difficulty:** Easy

### Level 10 — Automation (SOAR)
- **Objective:** Shuffle/n8n workflow — auto-enrichment + auto-block + notifikasi Telegram
- **Task:** replikasi workflow di REFERENSI.docx tapi dari nol dengan resource kamu sendiri
- **Estimasi:** 15–20 jam | **Difficulty:** Medium-Hard | **Portfolio value:** Sangat tinggi (jarang dipunya kandidat entry-level)

### Level 11 — Threat Hunting
- **Objective:** buat 2–3 hunting query proaktif di Wazuh (bukan menunggu alert)
- **Estimasi:** 6–8 jam | **Difficulty:** Medium

### Level 12 — Advanced Detection
- **Optional** — hanya jika Level 1–11 sudah solid dan masih ada waktu. Fokus: reduce false positive, tuning threshold.

### Level 13 — Cloud
- **Objective:** pindahkan honeypot (DVWA + Cowrie) ke Oracle Cloud, jadi log source tambahan yang realistis (exposed ke internet asli)
- **Estimasi:** 10 jam | **Difficulty:** Medium | Catatan: hati-hati exposure, isolasi network cloud dengan baik

### Level 14 — AI-assisted SOC (stretch goal)
- **Objective:** script kecil yang meringkas alert jadi draft triage note pakai LLM API
- **Estimasi:** 6–8 jam | **Difficulty:** Easy-Medium | Diferensiator kuat untuk 2026

---

## Z. Portfolio Presentation Strategy

- Repo baru `soc-l1-homelab` jadi **hub utama**, cross-link ke `enterprise-homelab-core` (infra) dan `redteam-homelab-toolkit` (offensive) yang sudah ada — reviewer bisa lihat progresi lengkap dari awal
- README dengan Security Score badge yang update tiap iterasi (bisa pakai shields.io custom badge, sesuai style repo kamu sekarang)
- Highlight di README: "5+ documented incident cases with full MITRE ATT&CK mapping, detection-as-code, dan measurable security score progression" — ini bahasa yang langsung nyambung ke JD SOC L1

---

## Catatan Realistis

- Jangan buru-buru ke Level 10+ sebelum Level 2–6 benar-benar solid dengan evidence lengkap — recruiter lebih percaya 5 case yang didokumentasikan detail daripada 14 level yang dangkal
- RAM 8GB bakal terasa di Level 2 ke atas (VirtualBox + Wazuh agent + browser + IDE). Kalau mulai terasa berat, pertimbangkan upgrade RAM (laptop kamu support upgrade) — ini investasi kecil dengan dampak besar untuk jangka panjang project ini
