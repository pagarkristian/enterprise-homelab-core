# Level 2–3: Logging & SIEM Setup — SOC-Manager (Wazuh)

> Checkpoint dokumentasi untuk `soc-l1-homelab`. Mencakup pembuatan VM SOC-Manager, instalasi Wazuh (manager + indexer + dashboard), dan seluruh proses troubleshooting nyata yang terjadi selama instalasi — disimpan sebagai bukti proses debugging, bukan hanya hasil akhir.

**Tanggal pengerjaan:** 18–20 Agustus 2026
**Status akhir:** ✅ Wazuh Manager, Indexer, dan Dashboard semua `active (running)`, dashboard dapat diakses dari browser host.

---

## 1. Arsitektur Akhir

| Komponen | Detail |
|---|---|
| Hypervisor | VirtualBox (host: Windows 11, MSI Thin GF63, i5-12450H, 8GB RAM) |
| VM baru | `SOC-Manager` — Ubuntu Server 24.04 LTS, 4GB RAM, 2 vCPU |
| Disk | Awal 25GB → di-extend ke 40GB (LVM) karena Wazuh stack butuh lebih banyak ruang dari perkiraan |
| Network | Host-only, `10.216.27.0/24` (satu segment dengan VM Ubuntu Target `.100`, Kali, MikroTik) |
| IP SOC-Manager | `10.216.27.50` |
| Software | Wazuh 4.14.7-1 (manager, indexer, dashboard), Filebeat 7.10.2-2 |

---

## 2. Pembuatan VM SOC-Manager

VM baru dibuat terpisah dari VM Ubuntu Target yang sudah ada — tujuannya memisahkan peran "yang dimonitor" (target) dari "yang memonitor" (SOC-Manager), sesuai arsitektur SOC yang realistis.

![VirtualBox Manager — VM existing](images/01-vbox-manager-existing-vms.png)
*VM lama (MikrotikOS, Ubuntu, Kali) dipertahankan sebagai fondasi — SOC-Manager ditambahkan di atasnya, bukan menggantikan.*

Setting VM:
- Base Memory: 4096 MB
- Processors: 2
- Disk: 25GB dynamically allocated (awal)

![VM creation settings](images/02-vm-creation-settings.png)

Dipilih **Ubuntu Server** (bukan Desktop) untuk SOC-Manager karena:
- Hemat RAM/CPU (tidak ada overhead GUI)
- Standar industri — server production hampir selalu headless/CLI
- Latihan skill yang relevan untuk role SOC L1 (kerja lewat terminal/SSH)

Saat instalasi, tidak ada snap tambahan yang dipasang (microk8s, nextcloud, dll semua di-skip) agar instalasi tetap ringan:

![Featured server snaps — semua di-skip](images/03-featured-server-snaps.png)

![Instalasi selesai](images/04-installation-complete.png)

---

## 3. Troubleshooting #1 — Host-only Network Tidak Bisa Diakses dari Windows

### Gejala
VM berhasil menyala dan mendapat IP di `10.216.27.50/24`, tapi Windows (host) **tidak bisa ping/SSH/akses dashboard** ke VM — meskipun VM-ke-VM (Ubuntu Target ↔ SOC-Manager) bisa saling ping.

![ip a awal — subnet mismatch](images/05-first-ip-a-mismatch-subnet.png)

### Proses debugging (ringkas)
1. **Cek adapter Windows** (`ipconfig`) — ditemukan ada 2 adapter di subnet yang mirip/overlap (VirtualBox Host-Only vs sisa VMware VMnet1/VMnet8) → VMware adapter di-disable.
2. **Cek Windows Firewall** (`netsh advfirewall`) — dimatikan sementara untuk isolasi masalah, ternyata bukan penyebabnya.
3. **Cek GlobalProtect VPN** — sempat dicurigai menyuntik routing table dengan banyak rute `10.0.x.x` yang bisa menabrak subnet lab. VPN di-disconnect, tapi masalah **tetap ada**.
4. **Cek routing table** (`route print -4`) — routing sebenarnya sudah benar.
5. **Cek Promiscuous Mode** di VirtualBox (Settings > Network > Adapter > Advanced) — defaultnya "Deny". Diubah ke **"Allow All"** → **ping langsung berhasil**.

Namun setelah itu ditemukan masalah lanjutan: **VMware Bridge Protocol** masih ter-bind di adapter Host-Only Windows (sisa instalasi VMware lama) — di-uncheck di Network Adapter Properties, tapi belum menyelesaikan masalah total.

### Root cause sebenarnya
Setelah berbagai perbaikan tidak stabil (kadang bisa, kadang tidak), ditemukan **host-only adapter Windows-nya sendiri corrupt** — dibuktikan lewat `VBoxManage.exe list hostonlyifs` yang menunjukkan IP tidak konsisten dengan `ipconfig`.

### Solusi final
Membuat **host-only adapter baru** dari command line (tanpa reinstall VirtualBox):
```powershell
cd "C:\Program Files\Oracle\VirtualBox"
.\VBoxManage.exe hostonlyif create
.\VBoxManage.exe hostonlyif ipconfig "VirtualBox Host-Only Ethernet Adapter #2" --ip 10.216.27.1 --netmask 255.255.255.0
.\VBoxManage.exe hostonlyif remove "VirtualBox Host-Only Ethernet Adapter"
```
Lalu **semua VM** (SOC-Manager, Ubuntu Target, Kali, MikroTik) di-reattach ke adapter baru (`#2`) lewat Settings > Network > Adapter > Name.

Hasil setelah fix:

![ping berhasil setelah adapter fix](images/06-ip-a-after-adapter-fix.png)

**Lesson learned:** kalau semua langkah standar (firewall, promiscuous mode, routing) sudah dicoba tapi host-only networking tetap bermasalah, curigai **corrupt host-only adapter di level VirtualBox/Windows** — solusi tercepat adalah membuat adapter baru dari nol via `VBoxManage`, bukan reinstall total.

---

## 4. Troubleshooting #2 — Konflik Netplan (Dua File Konfigurasi Bentrok)

### Gejala
```
WARNING: Permissions for /etc/netplan/01-netcfg.yaml are too open.
Invalid YAML: mapping values are not allowed in this context: via: 192.168.56.1
```
Interface `enp0s8` mendapat **dua IP sekaligus** (DHCP dari `50-cloud-init.yaml` + static dari file manual) → routing kacau, ping ke internet gagal.

![Netplan conflict error](images/07-netplan-conflict-error.png)

### Solusi
1. Hapus `50-cloud-init.yaml` (file otomatis dari cloud-init installer)
2. Matikan cloud-init network config generation permanen:
   ```bash
   sudo bash -c 'echo "network: {config: disabled}" > /etc/cloud/cloud.cfg.d/99-disable-network-config.cfg'
   ```
3. Pastikan hanya **satu file netplan** tersisa, isi bersih:
   ```yaml
   network:
     version: 2
     renderer: networkd
     ethernets:
       enp0s3:
         dhcp4: true
       enp0s8:
         dhcp4: no
         addresses:
           - 10.216.27.50/24
   ```
4. Fix permission: `sudo chmod 600 /etc/netplan/01-netcfg.yaml`
5. `sudo netplan apply`

![Netplan fixed, ping ke internet berhasil](images/08-netplan-fixed-ping-success.png)

**Lesson learned:** Ubuntu Server modern generate netplan config otomatis lewat cloud-init (`50-cloud-init.yaml`) — kalau menambahkan konfigurasi manual, file itu harus dihapus/didisable dulu, jangan cuma ditambah di file baru.

---

## 5. Troubleshooting #3 — Instalasi Wazuh: Disk Penuh Berulang Kali

### Gejala
Installer resmi (`wazuh-install.sh -a`) berulang kali gagal di step instalasi **dashboard** dengan error:
```
Error writing to file - write (28: No space left on device)
```
```
Error: ENOSPC: no space left on device, mkdir '/usr/share/wazuh-dashboard/data/wazuh'
```

### Root cause
1. **Partisi LVM Ubuntu hanya dialokasikan 12GB** dari disk fisik 25GB (sisanya "nganggur" — ini quirk umum installer Ubuntu dengan LVM).
2. Setelah di-extend ke 23GB penuh, **tetap penuh lagi** — karena banyak percobaan install/uninstall berulang menumpuk cache `apt` dan paket besar (indexer sendiri ~874MB per download).

### Solusi
**Extend LVM partition:**
```bash
sudo vgs   # cek free space di volume group
sudo lvextend -l +100%FREE ubuntu-vg/ubuntu-lv
sudo resize2fs /dev/mapper/ubuntu--vg-ubuntu--lv
```

Setelah penuh lagi di 23GB, disk **di-resize dari sisi VirtualBox** (VM dimatikan dulu):
```powershell
.\VBoxManage.exe modifymedium disk "path\to\SOC-Manager.vdi" --resize 40960
```
Lalu extend partition LVM lagi dari dalam VM:
```bash
sudo apt install cloud-guest-utils -y
sudo growpart /dev/sda 3
sudo lvextend -l +100%FREE ubuntu-vg/ubuntu-lv
sudo resize2fs /dev/mapper/ubuntu--vg-ubuntu--lv
```
Hasil akhir: 38GB total, ~14GB free — cukup lega untuk data indexer bertambah seiring waktu.

**Lesson learned:** alokasikan disk VM **minimal 40GB** sejak awal untuk SIEM stack (Wazuh indexer+manager+dashboard), jangan 25GB — instalasi awal saja sudah memakai ~7GB, ditambah data log/alert yang terus bertambah.

---

## 6. Troubleshooting #4 — Package `wazuh-manager` Corrupt, Installer Auto-Rollback Berulang

### Gejala
Installer script (`wazuh-install.sh -a -o`) **selalu rollback total** setiap kali dashboard gagal install — menghapus manager & indexer yang sebenarnya sudah berhasil, sehingga proses harus diulang dari nol setiap kali.

Ditemukan juga package `wazuh-manager` masuk status **"very bad inconsistent state"** — pre/post-removal script-nya sendiri error (exit code 127), sehingga `apt remove` maupun `dpkg --remove` biasa selalu gagal.

### Solusi
Bypass maintainer scripts yang corrupt secara manual:
```bash
sudo systemctl stop wazuh-manager
sudo pkill -9 -f wazuh
sudo rm -f /var/lib/dpkg/info/wazuh-manager.prerm
sudo rm -f /var/lib/dpkg/info/wazuh-manager.postrm
sudo dpkg --remove --force-remove-reinstreq --force-all wazuh-manager
sudo dpkg --purge --force-all wazuh-manager
sudo rm -rf /var/ossec
```
Setelah bersih total, install manual per-komponen (bukan lewat installer script all-in-one) untuk kontrol lebih baik:
```bash
sudo apt install wazuh-indexer wazuh-manager filebeat wazuh-dashboard -y
```

**Lesson learned:** installer script Wazuh (`wazuh-install.sh`) sangat agresif melakukan rollback total begitu ada satu komponen yang gagal — untuk environment dengan resource terbatas yang rawan gagal di tengah jalan (seperti lab ini), **instalasi manual per-komponen via `apt install`** jauh lebih terkontrol dan tidak kehilangan progress yang sudah berhasil.

---

## 7. Troubleshooting #5 — Dashboard Crash-Loop: Certificate Hilang

### Gejala
Semua service `active (running)`, tapi dashboard tidak bisa diakses dari browser (`ERR_CONNECTION_REFUSED`):

![Connection refused](images/12-connection-refused-dashboard.png)

Log menunjukkan:
```
Error: ENOENT: no such file or directory, open '/etc/wazuh-dashboard/certs/dashboard-key.pem'
```

### Root cause
Karena instalasi dilakukan manual per-komponen (bukan lewat installer all-in-one), certificate yang biasanya di-generate & di-deploy otomatis oleh installer **tidak pernah dibuat/dipasang**.

### Solusi
Certificate sebenarnya sudah pernah ter-generate di percobaan sebelumnya, tersimpan di `~/wazuh-install-files/` (folder ini sempat permission `700` root-only, perlu `sudo` untuk akses). Certificate di-copy manual ke lokasi yang tepat untuk masing-masing komponen:
```bash
# Dashboard
sudo mkdir -p /etc/wazuh-dashboard/certs
sudo cp ~/wazuh-install-files/root-ca.pem /etc/wazuh-dashboard/certs/
sudo cp ~/wazuh-install-files/wazuh-dashboard.pem /etc/wazuh-dashboard/certs/dashboard.pem
sudo cp ~/wazuh-install-files/wazuh-dashboard-key.pem /etc/wazuh-dashboard/certs/dashboard-key.pem
sudo chown -R wazuh-dashboard:wazuh-dashboard /etc/wazuh-dashboard/certs
sudo chmod 500 /etc/wazuh-dashboard/certs
sudo bash -c 'chmod 400 /etc/wazuh-dashboard/certs/*'

# Indexer & Filebeat: pola yang sama, disesuaikan nama file & folder tujuan
```

![Dashboard not ready yet — bootstrap awal](images/13-dashboard-not-ready-yet.png)

**Lesson learned:** kalau install manual per-komponen, certificate **wajib** dipasang manual juga — installer normal melakukan ini otomatis, tapi kalau bypass installer (seperti Troubleshooting #4), langkah ini gampang terlewat.

---

## 8. Troubleshooting #6 — Login Dashboard Selalu "Invalid Username or Password"

### Gejala
Password dari `wazuh-passwords.txt` selalu ditolak dashboard, meski sudah dicoba copy-paste berkali-kali.

![Invalid password](images/14-invalid-password-login.png)

### Root cause
Verifikasi langsung ke indexer API membuktikan password di file memang **tidak valid**:
```bash
curl -k -u 'admin:<password_dari_file>' https://localhost:9200
# → Unauthorized
```
Ternyata OpenSearch Security **belum pernah di-inisialisasi** di instalasi manual ini — proses ini biasanya dilakukan otomatis oleh installer script (`securityadmin.sh`), tapi terlewat karena instalasi manual.

### Solusi
1. Jalankan security init manual:
   ```bash
   export JAVA_HOME=/usr/share/wazuh-indexer/jdk
   sudo -E bash /usr/share/wazuh-indexer/plugins/opensearch-security/tools/securityadmin.sh \
     -cd /etc/wazuh-indexer/opensearch-security/ -nhnv \
     -cacert /etc/wazuh-indexer/certs/root-ca.pem \
     -cert /etc/wazuh-indexer/certs/admin.pem \
     -key /etc/wazuh-indexer/certs/admin-key.pem -h localhost
   ```
2. Password di file `wazuh-passwords.txt` ternyata **masih tidak sinkron** setelah init (sisa dari percobaan reinstall sebelumnya). Solusi: generate hash password baru sendiri dan suntik manual:
   ```bash
   sudo -E bash /usr/share/wazuh-indexer/plugins/opensearch-security/tools/hash.sh -p 'PasswordBaru123!'
   # copy hash yang dihasilkan, replace di internal_users.yml baris admin: hash: "..."
   sudo sed -i 's|hash: "OLD_HASH"|hash: "NEW_HASH"|' /etc/wazuh-indexer/opensearch-security/internal_users.yml
   # jalankan ulang securityadmin.sh dari step 1
   ```
3. Verifikasi: `curl -k -u 'admin:PasswordBaru123!' https://localhost:9200` → berhasil (JSON cluster info muncul).

![Dashboard berhasil diakses pertama kali](images/15-dashboard-first-success.png)

**Lesson learned:** kalau instalasi Wazuh dilakukan manual/terputus-putus, **OpenSearch Security init** (`securityadmin.sh`) adalah langkah wajib yang sering terlewat — tanpa ini, semua password default di `wazuh-passwords.txt` tidak akan valid sama sekali, meski file-nya sendiri terlihat benar.

---

## 9. Snapshot & Backup

Setelah semua service stabil, VM di-snapshot untuk mencegah harus mengulang seluruh proses di atas:

⚠️ **Catatan penting:** snapshot pertama sempat gagal (`VirtualBox - Critical Error`) karena diambil dalam kondisi **VM masih menyala (Online Snapshot)**, kemungkinan karena proses resize LVM sebelumnya membuat state disk sedang "sensitif".

![Snapshot critical error](images/16-snapshot-critical-error.png)

**Solusi:** matikan VM sepenuhnya dulu (`sudo shutdown now`, tunggu status "Powered Off" di VirtualBox Manager), baru ambil snapshot.

![Snapshot berhasil](images/17-snapshot-success.png)

**Lesson learned:** selalu ambil snapshot VirtualBox dalam kondisi VM **Powered Off**, terutama setelah operasi disk-level seperti resize LVM — Online Snapshot lebih rawan gagal.

---

## 10. Ringkasan Kredensial (referensi internal — JANGAN commit password asli ke Git)

| Keperluan | Lokasi |
|---|---|
| Dashboard login | `admin` / (lihat catatan pribadi, sudah direset manual — bukan default installer) |
| File asli password installer | `/home/manager/wazuh-install-files/wazuh-passwords.txt` (di VM, root-only) |
| Wazuh API | `wazuh` / `q1l3Na*DNK.u64HPkePSEJJVagUI0Bxg` *(ganti sebelum push ke publik!)* |

> 🔒 **Untuk versi yang di-push ke GitHub:** ganti seluruh tabel ini dengan placeholder (`<REDACTED>`), jangan pernah commit password asli meskipun ini lab pribadi.

---

## 11. Status Roadmap Setelah Checkpoint Ini

| Level | Status |
|---|---|
| 1. Infrastructure | ✅ Selesai |
| 2. Logging | ✅ SOC-Manager network solid, siap terima log |
| 3. SIEM | ✅ Wazuh dashboard live & dapat diakses |
| 4. Detection | ⬜ Berikutnya |
| 5. SOC L1 Triage | ⬜ Belum mulai |

**Total waktu troubleshooting Level 2-3:** ±2 hari (18-20 Agustus 2026), sebagian besar dihabiskan untuk masalah networking host-only dan disk/certificate management — bukan karena kompleksitas Wazuh itu sendiri, melainkan efek berantai dari instalasi yang berulang kali gagal di tengah jalan.
