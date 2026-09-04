# SSH Authentication Review — Internal Validation Traffic

## Summary
| Field | Detail |
|---|---|
| **Detection time** | 2026-08-21 01:30:49 – 01:30:53 UTC |
| **Detected by** | Wazuh (default ruleset, no custom rule needed) |
| **Rule ID** | 5710 |
| **Rule description** | sshd: Attempt to login using a non-existent user |
| **Rule level** | 5 (Medium) |
| **Affected asset** | `ubuntu-VirtualBox` (10.216.27.100) — TechSecure portal target host |
| **Source IP** | 10.216.27.50 |
| **MITRE ATT&CK** | T1110.001 (Password Guessing), T1021.004 (Remote Services: SSH) |
| **Tactic** | Credential Access, Lateral Movement |
| **Analyst** | Claudetier2 |
| **Status** | Closed — True Positive (authorized internal testing) |

---

## 1. Detection

Wazuh triggered rule `5710` four times within a 4-second window, each attempt using the non-existent username `anjing` against the SSH service on `ubuntu-VirtualBox`:

```json
{
  "rule": {"id": "5710", "level": 5, "description": "sshd: Attempt to login using a non-existent user"},
  "agent": {"name": "ubuntu-VirtualBox", "ip": "10.216.27.100"},
  "data": {"srcip": "10.216.27.50", "srcuser": "anjing"},
  "full_log": "Aug 21 01:30:49 ubuntu-VirtualBox sshd[6135]: Invalid user anjing from 10.216.27.50 port 52906"
}
```

Repeated at `01:30:51`, `01:30:52` (x2) — consistent with rapid, scripted/manual repeated login attempts rather than a single user typo.

## 2. Initial Triage

**Verification checklist:**
- [x] Confirmed event exists in `/var/ossec/logs/alerts/alerts.json` on manager
- [x] Confirmed event visible in Wazuh Dashboard (Threat Hunting → Events)
- [x] Confirmed source IP and destination host are both within internal lab network (`10.216.27.0/24`)
- [x] Confirmed timing correlates with known internal testing activity (SSH troubleshooting session)

**Severity assessment:** Level 5 (Medium) — appropriate for a *low-volume* failed-login pattern. Would escalate to High if:
- Attempt count exceeds ~10 in a short window (indicating automated brute-force tooling, e.g. Hydra)
- Source IP is external/untrusted
- A **successful** login follows shortly after failed attempts from the same source

## 3. Investigation

**Source IP `10.216.27.50` identification:**
- This IP belongs to `soc-manager` (the Wazuh Manager host itself) on the host-only lab network, not the designated Kali attacker (`10.216.27.10`, not yet used in this test).
- Investigation confirmed this was a **manually-triggered SSH connectivity test** performed during Level 4 detection validation — not a real external threat actor.

**IOC enrichment:**
- Source IP is internal/private (RFC1918), not eligible for VirusTotal/external threat intel lookup.
- No further external IOC correlation needed for this case.

**Timeline:**
| Time (UTC) | Event |
|---|---|
| 01:23:30 | Wazuh Agent on `ubuntu-VirtualBox` establishes connection to manager (`10.216.27.50:1514`) |
| 01:30:39 – 01:30:44 | Several SSH connections closed pre-authentication from `10.216.27.50` (valid user `ubuntu`, connection dropped before completing auth) |
| 01:30:49 – 01:30:52 | 4x failed login attempts using non-existent user `anjing` — **this triggered rule 5710** |
| 01:30:57 | Session on target host used to review `auth.log` (legitimate admin activity, same window) |

## 4. Verdict

**Classification: True Positive — but benign/authorized.**

The detection is technically accurate: SSH did receive failed login attempts against a non-existent username, and Wazuh correctly flagged it per its default ruleset and MITRE mapping. However, root cause analysis confirms this was **self-generated test traffic** from the SOC-Manager host during detection-pipeline validation (Level 4 testing), not malicious activity from an unauthorized actor.

This is recorded as a **legitimate detection event** to validate that:
1. The SSH brute-force detection pipeline (agent → manager → indexer → dashboard) works end-to-end
2. Default Wazuh rules provide baseline coverage for T1110.001 without requiring custom rule authoring
3. Analyst triage process (verify → investigate → classify → document) can be executed against real alert data

## 5. Recommended Action

- [x] No remediation needed — source was authorized internal activity
- [ ] **Future improvement:** configure `active-response` on rule group `authentication_failed` to auto-block source IP after N failed attempts within a time window (planned for Level 8 — Blue Team Hardening iteration)
- [ ] **Future improvement:** add a custom rule to distinguish "internal known-host testing" from "external untrusted source" using `srcip` CIDR matching, to reduce alert fatigue from expected internal testing noise

## 6. Escalation Decision

**No escalation required.** Case closed at Tier 1 — verified as authorized activity, documented for detection-coverage tracking purposes, and used as validation evidence for the `soc-l1-homelab` project's Level 4 milestone.

---

## Detection Coverage Contribution

| Technique (MITRE) | Attack Type | Detected | Investigated | Responded |
|---|---|---|---|---|
| T1110.001 | SSH failed login (invalid user) | ✅ (Wazuh default rule 5710) | ✅ | N/A (benign, no response needed) |

**Next planned test:** Trigger a higher-volume SSH brute-force from Kali (`10.216.27.10`) using Hydra against a real username, to validate rule `5712`/`5720` (multiple auth failures) escalation behavior and test whether Wazuh's active-response can auto-block dynamically.
