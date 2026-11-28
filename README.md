<div align="center">
<img src="https://img.shields.io/badge/Project-Enterprise_Homelab_Core-1F4068?style=for-the-badge&logo=proxmox&logoColor=white" />
<img src="https://img.shields.io/badge/Role-Blue_Team_/_Defender-0052CC?style=for-the-badge" />
<img src="https://img.shields.io/badge/Status-In_Progress-D29922?style=for-the-badge" />
</div>

<h1 align="center">Enterprise Homelab Core</h1>
<p align="center"><sub>A small-business-style network, built on purpose, broken on purpose, then watched.</sub></p>

---

## 🌟 What this is

A homelab built to model a small-business (SMB) network — a MikroTik router, an Ubuntu server running a simple internal web portal (`TechSecure`), and a Wazuh SIEM watching it. The project runs in two chapters:

- **v1.0.0 → v1.1.0** — build a deliberately weak server, attack it, then harden it and confirm the attacks no longer work.
- **v2.0.0** — add two new business features to the hardened server, deliberately build them with realistic developer mistakes, deploy a SIEM, confirm the mistakes are exploitable, confirm the default SIEM ruleset misses them, then write and prove custom detection rules for the gap.

The offensive half of the work — the actual attacks run against this server — is documented in the counterpart repository, **[redteam-homelab-toolkit](https://github.com/pagarkristian/redteam-homelab-toolkit)**. The two repos are meant to be read side by side: this one shows what was built and defended, that one shows what was attacked and how.

---

## 🗂️ Chapters

| Version | What happens | Where |
|---|---|---|
| **v1.0.0 → v1.1.0** | A server with real, common misconfigurations (SQL injection, anonymous FTP, weak SSH, open uploads) is built, attacked, then hardened — and re-attacked to confirm the fixes hold. | [`v1.0.0/`](./v1.0.0) |
| **v2.0.0** | On top of the hardened baseline, two new features are added with realistic logic flaws (IDOR, SQLi, command injection via upload). A Wazuh SIEM is deployed, the flaws are confirmed invisible to the default ruleset, custom detection rules are written and proven, and a SOC-style incident report closes the cycle. | [`v2.0.0/`](./v2.0.0) |

```diff
+[Build Vulnerable Server] ──> [Attack It] ──> [Harden It] ──> [Re-attack: Confirmed Blocked]
   (v1.0.0)                     (redteam repo)   (v1.1.0)
                                                      │
                                                      ▼
+[New Features, New Flaws] ──> [SIEM Deployed] ──> [Attack Again] ──> [Default Rules Miss It]
   (v2.1.0 / v2.1.1)              (v2.2.0)           (redteam repo, v2.2.1)
                                                                            │
                                                                            ▼
                                              [Write Detection Rules] ──> [Retest] ──> [Incident Report]
                                                     (v2.3.0)              (v2.3.1)      (v2.4.0)
```

---

## 🧰 Stack

MikroTik RouterOS · Ubuntu Server 24.04 · Apache/PHP/MySQL · Wazuh SIEM (manager, indexer, dashboard, agent) · VirtualBox (host-only networking, snapshots per phase)

---

## ✅ What this is meant to show

- Reading real logs (Apache `access.log`/`error.log`, Wazuh alerts) to confirm what actually happened, not assuming.
- Writing custom SIEM detection rules from scratch and debugging one that silently failed to fire (`wazuh-logtest`, root-caused, fixed).
- Understanding *why* a web application vulnerability is invisible to infrastructure-level monitoring, and what kind of rule is needed to close that specific gap.
- Distinguishing detection from remediation — the incident report in v2.0.0 recommends actual code fixes, not just "a rule now catches it."

## 📌 Current status

The full v1.0.0 → v2.0.0 cycle described above is built and documented. One item remains an open, honestly-flagged gap: exploiting the unrestricted file-extension path on the upload feature (uploading a `.php` file) was identified in code review but has not yet been tested end-to-end.

## 🤖 Note on AI assistance

The structure, wording, and formatting of this documentation were drafted with AI assistance. The lab itself — building the VMs and network, installing and configuring Wazuh, finding and exploiting the vulnerabilities, writing and testing the detection rules — was done hands-on. Every claim in these documents is backed by a screenshot or log excerpt taken during actual testing, not copied from a template.

---

<div align="center">
<sub>Enterprise Homelab Core · Maintained by <b>pagarkristian</b></sub>
</div>
