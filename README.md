# 🏛️ Enterprise Homelab Core: Architectural Evolution

<div align="center">
  <img src="https://img.shields.io/badge/Version-v1.1.0_Hardened-brightgreen?style=for-the-badge&logo=git&logoColor=white" alt="Version" />
  <img src="https://img.shields.io/badge/Linux-Ubuntu_24.04_LTS-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="OS" />
  <img src="https://img.shields.io/badge/Router-MikroTik_RouterOS-1F4068?style=for-the-badge&logo=mikrotik&logoColor=white" alt="Network" />
  <img src="https://img.shields.io/badge/Security-Secured_&_Audited-D32F2F?style=for-the-badge&logo=shieldos&logoColor=white" alt="Security" />
</div>

---

## 🌟 Overview

> [!NOTE]
> **Enterprise Homelab Core** dirancang sebagai cetak biru (*living blueprint*) infrastruktur jaringan skala *Small-to-Medium Business* (SMB) yang menerapkan filosofi **Software Development Lifecycle (SDLC)** dan prinsip **DevSecOps**.

> [!WARNING]
> Infrastruktur ini dibangun untuk **berevolusi secara organik**—berawal dari lingkungan operasional dasar yang sengaja dibuat rentan (*Vulnerable Baseline v1.0.0*), diuji melalui rangkaian audit penetrasi, hingga akhirnya diperkeras secara radikal menjadi lingkungan produksi yang aman (*Hardened Stable v1.1.0*).

---

## 🛠️ Arsitektur Jaringan & Topologi Logis

Berikut adalah pemetaan interkoneksi perangkat virtual di dalam laboratorium perimeter korporat:

| Perangkat | Antarmuka Host | Alokasi IP Address | Default Gateway | Peran & Fungsi Sistem |
| :--- | :--- | :--- | :--- | :--- |
| 🛡️ **Core Router** | `ether1` (WAN)<br>`ether2` (LAN) | DHCP dari ISP<br>`10.216.27.1/24` | Otomatis<br>N/A | Pusat Perutean, NAT, & Aturan Firewall Filter |
| 🐧 **Web Server** | `enp0s8` (Internal) | `10.216.27.100/24` | `10.216.27.1` | Host Portal Bisnis `TechSecure` & Layanan FTP |
| 🐉 **Kali Linux** | `eth1` (Internal) | `10.216.27.10/24` | `10.216.27.1` | Platform Konsol Auditor Keamanan & Uji Penetrasi |

---

## 🔄 Siklus Hidup Keamanan (Security Lifecycle)

Proyek ini menggunakan roda perputaran keamanan terintegrasi untuk menjamin stabilitas dan keamanan data:

```diff
 ┌────────────────────────────────────────────────────────┐
 │                                                        │
 ▼                                                        │
+[Build/Upgrade Lab] ──> [Audit & Scan] ──> [Hardening] ──┘
 (Blueprints Base)       (Docs/Laporan)     (Remediasi Kode)
       │
       ▼
-[Push Secure Config] ──> v1.0.0 ──> v1.1.0 ──> v2.0.0 (Next Integration)
