# Deploy TechSecure v2.0 ke Ubuntu Target

## 1. Transfer file ke VM Ubuntu Target

Dari SOC-Manager atau langsung download di VM Ubuntu Target (paling gampang: pakai `python3 -m http.server` dari mesin manapun yang punya filenya, sama seperti trik kita transfer wazuh-agent.deb kemarin).

## 2. Jalankan migrasi database

```bash
mysql -u pengelola -p techsecure_db < migration_v2.sql
```
(password: `password_db_123`)

## 3. Copy file PHP ke folder web

```bash
sudo cp -r api /var/www/html/
sudo cp -r profile /var/www/html/
sudo cp db.php reset-password.php /var/www/html/
sudo cp index.php /var/www/html/index.php   # menimpa index.php lama — pastikan backup dulu!
```

**PENTING — backup dulu index.php lama sebelum ditimpa:**
```bash
sudo cp /var/www/html/index.php /var/www/html/index.php.v1.1.bak
```

## 4. Set permission

```bash
sudo chown -R www-data:www-data /var/www/html/api /var/www/html/profile /var/www/html/db.php /var/www/html/reset-password.php
sudo mkdir -p /var/www/html/uploads
sudo chown www-data:www-data /var/www/html/uploads
```

## 5. Verifikasi PHP bisa akses internet (dibutuhkan buat VULN-04 SSRF)

```bash
php -r "var_dump(ini_get('allow_url_fopen'));"
```
Harus `bool(true)`. Kalau `false`, edit `/etc/php/*/apache2/php.ini`, cari `allow_url_fopen`, set ke `On`, lalu `sudo systemctl restart apache2`.

## 6. Test tiap endpoint dari browser Windows

- `http://10.216.27.100/index.php` — login pakai user seed: `budi.santoso` / `Budi12345`
- `http://10.216.27.100/api/v1/users/profile.php?id=1` — coba ganti id=1,2,3 (VULN-01)
- `http://10.216.27.100/reset-password.php` — coba request reset (VULN-02)
- `http://10.216.27.100/api/v1/reports/download.php?id=1` — coba ganti id (VULN-03)
- `http://10.216.27.100/profile/avatar-from-url.php` — coba import dari URL internal (VULN-04)

## Struktur file

```
/var/www/html/
├── index.php                          (updated — session support)
├── index.php.v1.1.bak                 (backup lama)
├── db.php
├── reset-password.php                 (VULN-02)
├── api/v1/
│   ├── users/profile.php              (VULN-01)
│   └── reports/download.php           (VULN-03)
├── profile/
│   └── avatar-from-url.php            (VULN-04)
└── uploads/                            (existing, dari v1.x)
```
