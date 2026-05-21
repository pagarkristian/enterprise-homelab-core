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
> **Enterprise Homelab Core** is a living architectural blueprint modeling a Small-to-Medium Business (SMB) enterprise network. This project demonstrates a full-lifecycle infrastructure evolution: originating from a fundamentally insecure baseline (**v1.0.0-vulnerable**), subjected to rigorous penetration testing, and iteratively mitigated via radical configuration hardening (**v1.1.0-hardened**).

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
| 🐧 **Target (Ubuntu)** | `enp0s8` (Internal LAN) | `10.216.27.100/24` | `10.216.27.1` | Hosts the internal `TechSecure` production portal and acts as the centralized FTP storage node. |
| 🐉 **Security Auditor (Kali Linux)**| `eth1` (Internal LAN) | `10.216.27.10/24` | `10.216.27.1` | Offensive testing endpoint utilized for continuous scanning, vulnerability assessment, and auditing. |

---



## 🔄 3. Security Lifecycle & Structural Versioning

This infrastructure is dynamically updated across version milestones to isolate remediation efforts:

```diff
 ┌────────────────────────────────────────────────────────┐
 │                                                        │
 ▼                                                        │
+[Build/Upgrade Lab] ──> [Audit & Scan] ──> [Hardening] ──┘
 (Blueprints Base)       (Docs/Report)      (Code Remediation)
       │
       ▼
-[Push Secure Config] ──> v1.0.0 (Vulnerable) ──> v1.1.0 (Hardened Stable) ──> v2.0.0 (Pipeline)

```

---

## 🔗 3. Cross-Project Linkage

This repository is an inseparable offensive counterpart to the main defensive project. To examine how the network architecture was built and how attacks from this arsenal were ultimately **blocked completely**, please visit the following links:

> [!TIP]
> * 🏛️ **Legacy Network Architecture (v1.0.0):** Review the unhardened router configurations and initial vulnerable server blueprints at the [Enterprise Homelab Core Repository (v1.0.0-vulnerable)]
(https://github.com/pagarkristian/enterprise-homelab-core/blob/main/v1.0.0-vulnerable.md).
> * 📄 **Official Retesting Report:** Read the comprehensive analysis of the failed attack (*Re-Exploit*) post-hardening in the [v1.1.0 Security Retesting Document]
(https://github.com/pagarkristian/enterprise-homelab-core/blob/4cb973737ffdf5014b13e16056fa0a8258ee8caa/Retesting%20-v1.1.0.md).
---

<div align="center">
  <sub>Maintained by <b>pagarkristian</b> for Cyber Security & Red Team Portfolio Standardization.</sub>
</div>
