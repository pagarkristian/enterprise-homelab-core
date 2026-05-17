# 🏛️ Enterprise Homelab Core: Architectural Evolution & Hardening

<div align="center">
  <img src="https://img.shields.io/badge/Infrastructure_Type-Enterprise_SMB_Core-1F4068?style=for-the-badge&logo=proxmox&logoColor=white" alt="Type" />
  <img src="https://img.shields.io/badge/Core_Router-MikroTik_RouterOS-0052CC?style=for-the-badge&logo=mikrotik&logoColor=white" alt="Router" />
  <img src="https://img.shields.io/badge/Production_OS-Ubuntu_Server_24.04_LTS-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="OS" />
  <img src="https://img.shields.io/badge/Security_Framework-DevSecOps_/_SDLC-D32F2F?style=for-the-badge&logo=target&logoColor=white" alt="Framework" />
</div>

---

## 🌟 1. Executive Overview

> [!IMPORTANT]
> **Enterprise Homelab Core** is a living architectural blueprint modeling a Small-to-Medium Business (SMB) enterprise network. This project demonstrates a full-lifecycle infrastructure evolution: originating from a fundamentally insecure baseline (**v1.0.0-vulnerable**), subjected to rigorous penetration testing, and iteratively mitigated via radical configuration pengerasan (**v1.1.0-hardened**).

### 🎯 Core Infrastructure Pillars:
* 🔹 **Infrastructure as Code (IaC) Mindset:** All device configurations, firewall matrices, and system rules are version-controlled and documented as code.
* 🔹 **Secured SDLC Execution:** Implements continuous security evaluation cycles to track down architectural regression from initial deployment to stable releases.
* 🔹 **Defense-in-Depth Emulation:** Combines industry-standard routing platforms (MikroTik RouterOS) and enterprise server environments (Ubuntu Server) within a controlled virtual boundary.

---

## 🛠️ 2. Core Network Architecture & Topology Matrix

The table below details the logical multi-segment network layout and asset distribution within the enterprise lab:

| System Component | Host Interface | Assigned IP Address | Default Gateway | Functional Role & Network Scope |
| :--- | :--- | :--- | :--- | :--- |
| 🛡️ **Core Router (MikroTik)** | `ether1` (WAN)<br>`ether2` (LAN) | DHCP (ISP Assigned)<br>`10.216.27.1/24` | Automated<br>N/A | Core routing, NAT management, boundary isolation, and central Firewall Filter rule enforcement. |
| 🐧 **Web & FTP Server (Ubuntu)** | `enp0s8` (Internal LAN) | `10.216.27.100/24` | `10.216.27.1` | Hosts the internal `TechSecure` production portal and acts as the centralized FTP storage node. |
| 🐉 **Security Auditor (Kali Linux)**| `eth1` (Internal LAN) | `10.216.27.10/24` | `10.216.27.1` | Offensive testing endpoint utilized for continuous scanning, vulnerability assessment, and auditing. |

---
---

## 🔄 Security Life Cycle 

This project uses an integrated security wheel to ensure data stability and security:

```diff
 ┌────────────────────────────────────────────────────────┐
 │                                                        │
 ▼                                                        │
+[Build/Upgrade Lab] ──> [Audit & Scan] ──> [Hardening] ──┘
 (Blueprints Base)       (Docs/Report)     (Code Remediation)
       │
       ▼
-[Push Secure Config] ──> v1.0.0 ──> v1.1.0 ──> v2.0.0 (Next Integration)

```
---
##  Milestone Specifications (Click to Expand Detail)
🛡️ Posture Status: SECURED & VERIFIED
Engineered following deep network penetration testing to close major systemic entry points. Focus centers on traffic encryption and access containment.

🛠️ Hardening Vector Remediations:
[Transport Cryptography] Enforced Apache SSL/TLS modules (a2enmod ssl). Forced cleartext traffic migration from HTTP Port 80 directly onto encrypted HTTPS Port 443.

[Access Control Restriction] Deprecated unauthenticated access vectors within vsftpd.conf to completely disable Anonymous Logins.

[Boundary Firewall Update] Synchronized tight MikroTik RouterOS IP Firewall Filter Rules to drop unauthorized cross-segment packets targeting internal assets.

## 📂 Resource Links:

🔗 Review v1.1.0 Secure Configuration Blueprints

## 📄 Read the Post-Hardening Verification Report

## ⚠️ Posture Status: VULNERABLE BY DESIGN
The foundational release mimicking high-risk infrastructure flaws commonly found in enterprise environments due to systemic administrative misconfigurations.

---
## ❌ Open Vulnerability Vectors:
[Insecure Communication Protocols] Applications served over cleartext HTTP, exposing business transaction data to active sniffing payloads.

[Flawed Authentication Design] Permissive FTP configuration allows unauthenticated threat actors to extract critical backend directory assets.

[Arbitrary Code Upload Endpoint] The web directory lacks extension or content-type sanitization (chmod 777), enabling successful WebShell injections to achieve full RCE.

## 📂 Resource Links:

🔗 Review v1.0.0 Vulnerable Source Blueprints

📄 Read the Initial Architectural Writeup

## 🤖 5. Defensive Integration (Cross-Project Linkage)
To examine the active penetration testing lifecycle, automated exploitation scripts, and offensive toolkits used to audit this infrastructure, refer to the twin security node:

[!TIP]

🎯 Offensive Arsenal: Review active payload development and automated exploit verification scripts at the Red Team Arsenal Repository.
