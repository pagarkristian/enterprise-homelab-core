<div align="center">
  <img src="https://img.shields.io/badge/Infrastructure_Type-SMB_Homelab-1F4068?style=for-the-badge&logo=proxmox&logoColor=white" alt="Type" />
  <img src="https://img.shields.io/badge/Core_Router-MikroTik_RouterOS-0052CC?style=for-the-badge&logo=mikrotik&logoColor=white" alt="Router" />
  <img src="https://img.shields.io/badge/Target_OS-Ubuntu_Server_24.04_LTS-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="OS" />
  <img src="https://img.shields.io/badge/Status-Done-1F883D?style=for-the-badge" />
</div>

# v1.0.0 → v1.1.0: Build, Break, Harden, Re-test

<div align="center"><sub>The first cycle of the project: a deliberately weak server is built, attacked, then hardened — and attacked again to confirm the fixes actually hold.</sub></div>

---

## 🌟 1. Overview

This is the starting point of the homelab. The goal was to build a small network with real, common misconfigurations — not contrived ones — attack it properly, and then fix each weakness one at a time, confirming with a re-attack that the fix actually closed the gap rather than just looking closed.

## 🛠️ 2. Network Topology

| System Component | Host Interface | Assigned IP | Default Gateway | Role |
| :--- | :--- | :--- | :--- | :--- |
| 🛡️ Core Router (MikroTik) | `ether1` (WAN), `ether2` (LAN) | DHCP (WAN), `10.216.27.1/24` (LAN) | — | NAT, perimeter firewall rules |
| 🐧 Target (Ubuntu) | `enp0s8` (internal LAN) | `10.216.27.100/24` | `10.216.27.1` | Hosts the `TechSecure` portal and FTP storage |
| 🐉 Auditor (Kali Linux) | `eth1` (internal LAN) | `10.216.27.10/24` | `10.216.27.1` | Runs the scans and attacks |

## 🔄 3. What happens in this cycle

```diff
+[Build Vulnerable Baseline] ──> [Attack It] ──> [Harden It] ──> [Re-attack: Confirmed Blocked]
   (v1.0.0-vulnerable.md)      (redteam repo)  (v1.1.0-hardened.md)  (v1.1.0-Retesting.md)
```

- **[v1.0.0-vulnerable.md](./v1.0.0-vulnerable.md)** — the network build-out and the vulnerable server configuration, with every flaw deliberately left in: anonymous FTP, an open Samba share, a SQL-injectable login, and an unrestricted file upload.
- The full attack against this baseline (SQL injection, web shell upload, SSH brute-forcing, flag capture) is documented in the counterpart repository — see [redteam-homelab-toolkit, v1.0.0](https://github.com/pagarkristian/redteam-homelab-toolkit/tree/main/v1.0.0).
- **[v1.1.0-hardened.md](./v1.1.0-hardened.md)** — the fixes applied for each weakness found: firewall rules, SSH key-only auth, prepared statements, upload whitelisting, Fail2Ban, and MikroTik perimeter rules.
- **[v1.1.0-Retesting.md](./v1.1.0-Retesting.md)** — confirms, from the defender's side, that every attack from the baseline now fails.

---

<div align="center">
  <sub>Enterprise Homelab Core v1.0.0 · Maintained by <b>pagarkristian</b></sub>
</div>
