<div align="center">
<img src="https://img.shields.io/badge/Project-Enterprise_Homelab_Core-1F4068?style=for-the-badge&logo=proxmox&logoColor=white" />
<img src="https://img.shields.io/badge/Version-2.0.0-0052CC?style=for-the-badge" />
<img src="https://img.shields.io/badge/Status-In_Progress-D29922?style=for-the-badge" />
</div>

# v2.0.0: SOC Integration

The server passed its audit in v1.1.0. Then the business asked for two new features, and this chapter finds out whether anyone would notice if they went wrong.

## Contents

| # | Document | Status |
|---|----------|--------|
| 1 | [Business Context](./v2.1.0-business-context.md) | Done |
| 2 | [Vulnerable Features](./v2.1.1-vulnerable-features.md) | Done |
| 3 | Monitoring Setup (Wazuh) | Done |
| 4 | Attack Simulation (in [redteam-homelab-toolkit](https://github.com/pagarkristian/redteam-homelab-toolkit)) | Planned |
| 5 | Detection Engineering | Planned |
| 6 | Retesting | Planned |
| 7 | Incident Report | Planned |

## Current lab state

- Wazuh server and agent are connected (agent `techsecure-target`, status active).
- Two intentionally vulnerable features are deployed on the TechSecure portal.
- Next: attack simulation from Kali, then detection rules for whatever the default rules miss.

---

<div align="center">
<sub>Enterprise Homelab Core v2.0.0 · Maintained by <b>pagarkristian</b></sub>
</div>

