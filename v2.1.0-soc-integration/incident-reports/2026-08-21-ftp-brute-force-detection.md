# FTP Brute Force Detection — Correlation Rule Escalation

## Summary
| Field | Detail |
|---|---|
| **Detection time** | 2026-08-21 03:12:17 – 03:12:20 UTC |
| **Detected by** | Wazuh (default ruleset — correlation rule) |
| **Rule IDs** | 5503 (individual failure, level 5) → **5551 (correlated escalation, level 10)** |
| **Rule description** | PAM: Multiple failed logins in a small period of time |
| **Rule level** | **10 (High)** |
| **Affected asset** | `ubuntu-VirtualBox` (10.216.27.100) — TechSecure portal target host |
| **Affected service** | vsftpd (FTP), port 21 |
| **Source IP** | 10.216.27.10 (Kali — designated attacker node) |
| **Target username** | `ubuntu` |
| **MITRE ATT&CK** | T1110 (Brute Force) |
| **Tactic** | Credential Access |
| **Compliance impact** | PCI DSS 10.2.4/10.2.5/11.4, HIPAA 164.312.b, GDPR IV_35.7.d/IV_32.2, NIST 800-53 AU.14/AC.7/SI.4 |
| **Analyst** | Claudetier2 |
| **Status** | Closed — True Positive (authorized red team exercise) |

---

## 1. Detection

Attack tool: **Hydra v9.7**, executed from Kali (`10.216.27.10`) against FTP service on `ubuntu-VirtualBox`:
```bash
hydra -l ubuntu -P /tmp/small_wordlist.txt -t 4 10.216.27.100 ftp
```

The attack generated 8+ rapid authentication failures against the FTP service (`vsftpd`) within a ~3-second window (03:12:17 – 03:12:20 UTC), all targeting username `ubuntu` with different passwords from a 10-entry custom wordlist.

**This is a textbook example of a Wazuh correlation rule in action:**

| Rule | Trigger condition | Level | Role |
|---|---|---|---|
| 5503 | Single PAM authentication failure | 5 (Medium) | Base event — fires on *every* failed login |
| **5551** | 8 occurrences of rule 5503, within 180s, same source IP | **10 (High)** | **Correlation rule** — escalates severity when a pattern (not just a single event) is detected |

This demonstrates the difference between **atomic detection** (single suspicious event) and **behavioral detection** (pattern of events over time) — a core SIEM concept.

## 2. Raw Evidence

```
Aug 21 03:12:20 ubuntu-VirtualBox vsftpd[8056]: pam_unix(vsftpd:auth): authentication failure;
  logname= uid=0 euid=0 tty=ftp ruser=ubuntu rhost=::ffff:10.216.27.10 user=ubuntu
```
(x8, timestamps 03:12:17 – 03:12:20, PIDs 8056–8059, each representing one Hydra thread — `-t 4` parallel connections explains the near-simultaneous timestamps)

**Rule 5551 correlation logic** (from Wazuh ruleset `0085-pam_rules.xml`):
```
frequency: 8        (minimum occurrences to trigger escalation)
timeframe: 180       (rolling window in seconds)
if_matched_sid: 5503 (base rule this correlates against)
same_source_ip: true (correlation is scoped per unique source IP)
```

## 3. Triage

**Verification checklist:**
- [x] Confirmed `data.srcip` = `10.216.27.10` — matches known Kali attacker node, not internal/authorized management traffic
- [x] Confirmed target service (FTP) and account (`ubuntu`) match the exercise scope
- [x] Confirmed timing correlates with manually-executed Hydra command (self-initiated red team test)
- [x] Confirmed escalation rule 5551 fired correctly — validates that Wazuh's default correlation engine is functioning as expected

**Severity justification (Level 10 vs Level 5):**
Unlike this report (a handful of scattered failed logins), this event represents a **sustained, rapid, single-source brute-force pattern** — exactly the signature correlation rules are designed to catch. The jump from level 5 → 10 is appropriate and demonstrates the value of correlation over single-event alerting (a single level-5 alert might be lost in noise; a level-10 alert demands immediate analyst attention).

## 4. Investigation

**Attacker profile:**
- Source: `10.216.27.10` (Kali Linux, lab-designated red team node)
- Tool: Hydra 9.7 (industry-standard credential brute-forcing tool)
- Attack pattern: 4 parallel threads (`-t 4`), 10-password wordlist, single username

**Target impact assessment:**
- **0 successful authentications** — Hydra reported "0 valid password found"
- FTP service remained available throughout (no DoS impact observed)
- No evidence of subsequent lateral movement or privilege escalation following the failed attempts

**Gap identified:** SSH on the same host was *not* vulnerable to this same technique — `hydra ... ssh` failed immediately with `does not support password authentication`, confirming SSH key-only auth hardening (from the `enterprise-homelab-core` v1.1.0-hardened baseline) is still effective. **FTP, however, still accepts password authentication and has no rate-limiting or lockout mechanism**, making it a softer target than SSH on this host.

## 5. Verdict

**Classification: True Positive — authorized red team exercise, successfully detected and correctly escalated.**

This case validates three things simultaneously:
1. Wazuh's default ruleset detects FTP brute force without any custom rule authoring
2. The correlation engine (frequency + timeframe + same-source-IP) correctly escalates severity for sustained attack patterns vs. isolated failures
3. A real security gap exists: FTP lacks the same hardening SSH already received

## 6. Recommended Action

- [x] **Immediate:** No live remediation needed — attack was contained to lab environment, no successful compromise
- [ ] **Hardening backlog (Level 8):**
  - Configure `vsftpd` account lockout after N failed attempts (e.g., via `pam_faillock` or `fail2ban`)
  - Consider disabling password auth for FTP entirely in favor of SFTP-over-SSH-key, mirroring the SSH hardening already applied
  - Add Wazuh `active-response` to auto-block source IP on rule 5551 trigger (auto-containment, not just detection)
- [ ] **Detection backlog:** current detection relies entirely on Wazuh's built-in PAM correlation rule. A custom rule could add FTP-specific context (e.g., tagging the specific service in the alert title) for faster analyst triage — planned for this report exercise.

## 7. Escalation Decision

**No further escalation required** — closed at Tier 1. Documented as validation evidence that the SOC-Manager's correlation detection is functioning correctly against a real (if lab-contained) brute-force tool, and as the basis for the FTP hardening item added to the Level 8 backlog.

---

## Detection Coverage Contribution

| Technique (MITRE) | Attack Type | Detected | Correlated/Escalated | Investigated | Responded |
|---|---|---|---|---|---|
| T1110 | FTP Brute Force (Hydra) | ✅ (rule 5503) | ✅ (rule 5551, level 5→10) | ✅ | ⬜ (active-response not yet configured — Level 8 backlog) |
| T1110.001 | SSH Brute Force | N/A — blocked at auth layer (key-only, no password fallback) | — | — | ✅ (hardening already effective) |

**Comparison to this report:** this report was a low-volume, benign, internally-sourced event (level 5, no escalation). this report is a genuine attack simulation from the designated red team node, correctly triggering the high-severity correlation rule — a much stronger demonstration of both attacker technique and detection capability for portfolio purposes.
