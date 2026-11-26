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
| 3 | [Monitoring Setup (Wazuh)](./v2.2.0-monitoring-setup.md) | Done |
| 4 | [Attack Simulation](https://github.com/pagarkristian/redteam-homelab-toolkit/blob/main/v2.0.0/v2.2.1-attack-simulation.md) (in redteam-homelab-toolkit) | Done |
| 5 | [Detection Engineering](./v2.3.0-detection-engineering.md) | Done |
| 6 | [Retesting](./v2.3.1-retesting.md) | In progress |
| 7 | [Incident Report](./v2.4.0-incident-report.md) | In progress |

## Current lab state

- Wazuh server and agent are connected (agent `techsecure-target`, status active). Apache logs verified reaching the SIEM.
- Two intentionally vulnerable features are deployed on the TechSecure portal.
- IDOR, SQL injection, and command injection confirmed exploitable. None were caught by Wazuh's default ruleset (all three produce HTTP 200 responses, which the default rules mostly ignore).
- Three custom rules written and confirmed firing: `100100` (SQLi pattern), `100101`/`100102` (IDOR scan via frequency), `100103` (upload command injection, via the convert failure signature — fixed after a real debugging session with `wazuh-logtest` revealed an `if_group web` condition was silently blocking it).
- Next: retest every attack "cold" against the now-active ruleset, confirm no false positives on legitimate use, then write the incident report.

## Note on AI assistance

The structure, wording, and formatting of this documentation were drafted with AI assistance. The lab itself — building the VMs and network, installing and configuring Wazuh, finding and exploiting the vulnerabilities, writing and testing the detection rules — was done hands-on. Every claim in these documents is backed by a screenshot or log excerpt taken during actual testing, not copied from a template.

---

<div align="center">
<sub>Enterprise Homelab Core v2.0.0 · Maintained by <b>pagarkristian</b></sub>
</div>
