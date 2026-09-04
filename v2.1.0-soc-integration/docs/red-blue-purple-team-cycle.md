# Level 7 — Red Team Simulation: Panduan Detail Tahap 4-7

> Lanjutan dari TechSecure v2.0. Tahap 1-3 (Build, Attack, Gap Analysis) sudah selesai — 4 vuln terbukti berfungsi, terbukti TIDAK terdeteksi Wazuh default rule. Dokumen ini panduan detail untuk 4 tahap tersisa.

---

## TAHAP 4: Custom Detection

### 4.1 — Pastikan Apache access log terbaca Wazuh

**Kenapa perlu:** Wazuh gak otomatis baca `/var/log/apache2/access.log`. Perlu didaftarkan manual di config agent.

**Langkah (di VM Ubuntu Target):**

```bash
sudo nano /var/ossec/etc/ossec.conf
```

Cari tag `<ossec_config>`, tambahkan block ini di dalamnya (sebelum `</ossec_config>` penutup):

```xml
<localfile>
  <log_format>apache</log_format>
  <location>/var/log/apache2/access.log</location>
</localfile>
```

Simpan, restart agent:
```bash
sudo systemctl restart wazuh-agent
```

**Verifikasi:** generate 1 request test (buka `http://10.216.27.100:8080/index.php` dari browser), lalu cek di dashboard Wazuh (Threat Hunting → Events, search `apache`) — harusnya sekarang ada event masuk meskipun levelnya rendah/generic (belum ada rule spesifik).

### 4.2 — Tulis custom rule pertama: deteksi pattern IDOR scanning (VULN-01)

**Konsepnya:** kalau ada 1 IP yang akses `/api/v1/users/profile.php?id=` dengan angka `id` yang **berbeda-beda** berkali-kali dalam waktu singkat — itu pola scanning/enumeration, bukan pemakaian normal (user normal cuma akses profile-nya sendiri, gak ganti-ganti ID).

**Di SOC-Manager:**

```bash
sudo nano /var/ossec/etc/rules/local_rules.xml
```

Kalau file belum ada, buat dulu dengan struktur dasar:
```xml
<group name="local,techsecure,">

  <!-- Base rule: catch any access to the profile API endpoint -->
  <rule id="100010" level="3">
    <if_sid>31100</if_sid>
    <url>/api/v1/users/profile.php</url>
    <description>TechSecure API: User profile endpoint accessed</description>
  </rule>

  <!-- Correlation rule: same source IP hitting this endpoint many times = possible IDOR scanning -->
  <rule id="100011" level="10" frequency="5" timeframe="60">
    <if_matched_sid>100010</if_matched_sid>
    <same_source_ip />
    <description>TechSecure: Possible IDOR enumeration on user profile API (rapid repeated access)</description>
    <mitre>
      <id>T1213</id>
    </mitre>
  </rule>

</group>
```

**Catatan penting soal `<if_sid>31100</if_sid>`:** ini merujuk ke rule dasar grup `web,accesslog` bawaan Wazuh (yang menangkap semua baris Apache access log). Sebelum pakai angka ini, verifikasi dulu ID rule Apache generic yang benar-benar cocok — cek dokumentasi Wazuh atau cari lewat dashboard (Rules management, cari grup `web`).

Simpan file, lalu:
```bash
sudo systemctl restart wazuh-manager
```

### 4.3 — Tulis rule kedua: deteksi BOLA report download (VULN-03)

Sama pola seperti di atas, tapi untuk endpoint report:
```xml
<rule id="100012" level="3">
  <if_sid>31100</if_sid>
  <url>/api/v1/reports/download.php</url>
  <description>TechSecure API: Report download endpoint accessed</description>
</rule>

<rule id="100013" level="10" frequency="3" timeframe="60">
  <if_matched_sid>100012</if_matched_sid>
  <same_source_ip />
  <description>TechSecure: Possible BOLA - rapid report enumeration detected</description>
  <mitre>
    <id>T1005</id>
  </mitre>
</rule>
```

### 4.4 — Tulis rule ketiga: deteksi SSRF attempt (VULN-04, paling penting)

SSRF ini beda — yang perlu dicurigai adalah **isi parameter URL yang dikirim**, bukan cuma frekuensi akses. Kalau parameter `avatar_url` mengandung IP privat/internal atau skema `file://`, itu jelas mencurigakan.

```xml
<rule id="100014" level="12">
  <if_sid>31100</if_sid>
  <url>/profile/avatar-from-url.php</url>
  <regex type="pcre2">avatar_url=(https?://(127\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)|file://)</regex>
  <description>TechSecure: Possible SSRF attempt - internal/private URL or file:// scheme in avatar import</description>
  <mitre>
    <id>T1090</id>
  </mitre>
</rule>
```

Simpan semua, restart manager:
```bash
sudo systemctl restart wazuh-manager
```

**Verifikasi rule diterima tanpa syntax error:**
```bash
sudo /var/ossec/bin/wazuh-logtest
```
(Ini mode interaktif — kamu bisa paste contoh baris log Apache dan lihat apakah rule kamu ke-trigger. Ketik `Ctrl+C` untuk keluar.)

---

## TAHAP 5: Retest

Ulangi persis serangan yang sama seperti Tahap 2 (Attack):
- Akses `profile.php?id=1,2,3,4,5` berkali-kali cepat
- Akses `download.php?id=1,2,3` berkali-kali cepat
- Kirim `avatar_url=http://10.216.27.1:8291/` ke SSRF endpoint

**Cek dashboard Wazuh** (Threat Hunting → Events, filter "Last 15 minutes") — kali ini seharusnya **muncul alert** dengan rule ID 100011, 100013, atau 100014 yang barusan kamu buat.

**Kalau belum muncul**, debug pakai `wazuh-logtest` (masukkan baris asli dari `/var/log/apache2/access.log` yang sesuai, lihat apakah rule match).

**Dokumentasikan** hasil retest ini sebagai case baru: `INC-0003` (misal untuk IDOR yang sekarang berhasil terdeteksi), dengan format yang sama seperti INC-0001/0002 — sertakan bukti before (Tahap 3, gak terdeteksi) vs after (Tahap 5, terdeteksi).

---

## TAHAP 6: Harden

Perbaiki kode PHP-nya (bukan cuma deteksi doang, tapi tutup vuln-nya beneran):

**Fix VULN-01 (IDOR):** di `api/v1/users/profile.php`, tambahkan pengecekan bahwa `$id` yang diminta harus sama dengan `$_SESSION['user_id']` (kecuali user itu admin):
```php
if ($id !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit();
}
```

**Fix VULN-02 (Predictable token):** ganti `substr(md5(time()), 0, 8)` jadi token random yang secure:
```php
$token = bin2hex(random_bytes(16));
```

**Fix VULN-03 (BOLA):** sama pola seperti VULN-01, tambahkan cek kepemilikan report di `download.php`:
```php
$stmt = $conn->prepare("SELECT title, content FROM reports WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
```

**Fix VULN-04 (SSRF):** whitelist domain yang diizinkan, tolak IP privat:
```php
$parsed = parse_url($url);
$host = $parsed['host'] ?? '';
$ip = gethostbyname($host);
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
    die("URL tidak diizinkan.");
}
```

---

## TAHAP 7: Final Retest

Ulangi Tahap 5 sekali lagi **setelah** hardening di Tahap 6:
- Serangan yang sama seharusnya sekarang **gagal dieksploitasi** (proteksi sudah aktif)
- **DAN** tetap **terdeteksi** oleh custom rule (percobaan serangan tetap tercatat meski gagal — ini penting, bukti defense-in-depth: gagal dieksploitasi + tetap ada jejak audit)

Dokumentasikan sebagai penutup siklus — update `SCORE.md` / Detection Coverage Matrix dengan status akhir semua 4 teknik.

---

## Ringkasan Alur Kerja

```
Tahap 4 (Custom Detection) → tulis 3 rule baru di local_rules.xml
Tahap 5 (Retest)            → serang ulang, buktikan sekarang terdeteksi → tulis INC-0003
Tahap 6 (Harden)            → perbaiki kode PHP, tutup vuln beneran
Tahap 7 (Final Retest)      → serang ulang lagi, buktikan gagal + tetap terdeteksi → tulis INC-0004
```

Setiap tahap = bukti nyata untuk portofolio: kamu paham gap analysis, bisa menulis detection rule dari nol, tahu cara memperbaiki kode secara aman, dan bisa memvalidasi hasil hardening dengan retest — siklus purple-team penuh.
