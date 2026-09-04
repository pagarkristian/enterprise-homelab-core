# Detection Engineering — Custom Rule for IDOR Enumeration

## Summary
| Field | Detail |
|---|---|
| **Vulnerability** | VULN-01 — IDOR on `/api/v1/users/profile.php` (TechSecure v2.0) |
| **Detection method** | **Custom rule** (`local_rules.xml`, rule ID 100010/100011) — NOT a default Wazuh rule |
| **Rule level** | 10 (High), after correlation |
| **Affected asset** | `ubuntu-VirtualBox` (10.216.27.100) |
| **Source IP (test)** | 10.216.27.1 (analyst workstation, simulating attacker) |
| **MITRE ATT&CK** | T1213 (Data from Information Repositories) |
| **OWASP** | A01:2021 Broken Access Control |
| **Analyst** | Claudetier2 |
| **Status** | Closed — Detection Engineering exercise successful |

---

## 1. Background: Gap Analysis

Following the design of TechSecure v2.0 (see `techsecure-v2-design.md`), VULN-01 (IDOR on the user profile API) was manually exploited by requesting `profile.php?id=1`, `id=2`, `id=3` etc. as an authenticated low-privilege user — successfully retrieving other users' profile data.

**Initial check against Wazuh's default ruleset confirmed zero detection.** This was expected: default rules cover authentication events (PAM, sshd) and generic web server errors, but have no awareness of application-specific access-control logic like IDOR. A raw HTTP 200 response to `/api/v1/users/profile.php?id=X` is indistinguishable from legitimate traffic without custom logic.

## 2. Detection Engineering Process

### 2.1 — Log source discovery (unexpected obstacle)

The first blocker wasn't rule-writing — it was **finding where the relevant logs actually lived**. Two issues surfaced:
1. Apache's default `access.log` was empty (0 bytes); actual traffic was being written to `other_vhosts_access.log` because TechSecure is served via a named virtual host rather than the default site.
2. The custom Apache port (8080, from prior hardening) was not allowed through `ufw`, initially causing "connection timed out" errors that were mistaken for a rule problem before being correctly diagnosed as a firewall gap.

Both had to be resolved before any rule could be validated — a reminder that **detection engineering starts with confirming the pipeline delivers the right data**, not with writing rule XML.

### 2.2 — Rule design

Two-tier rule structure, following the same pattern Wazuh itself uses for its PAM correlation rules (see this report):

```xml
<!-- Base rule: tags any request to the vulnerable endpoint -->
<rule id="100010" level="3">
  <if_sid>31100</if_sid>
  <url>/api/v1/users/profile.php</url>
  <description>TechSecure API: User profile endpoint accessed</description>
</rule>

<!-- Correlation rule: escalates when the SAME source IP hits this
     endpoint repeatedly in a short window — the signature of ID
     enumeration, not normal single-profile-lookup usage -->
<rule id="100011" level="10" frequency="5" timeframe="60">
  <if_matched_sid>100010</if_matched_sid>
  <same_source_ip />
  <description>TechSecure: Possible IDOR enumeration on user profile API (rapid repeated access)</description>
  <mitre>
    <id>T1213</id>
  </mitre>
</rule>
```

**Design rationale:** a single request to this endpoint is completely normal (any logged-in user is expected to view *a* profile). What distinguishes an IDOR enumeration attempt is the **pattern** — many requests, same source, short window, varying `id` parameter. This mirrors exactly how Wazuh's own `5551` PAM rule (seen in this report) escalates severity based on frequency rather than flagging every single event.

### 2.3 — Validation with `wazuh-logtest`

Initial testing with `wazuh-logtest` using a truncated log line caused a **false decoder match** — the log was mis-identified as a "Zeus" botnet log rather than an Apache access log, because the pasted line was missing its `127.0.1.1:80 10.216.27.1 - -` prefix. This was a useful reminder that **decoders match on exact log structure**, not just keyword presence; testing with incomplete sample data produces misleading results.

With the full, correctly-formatted log line, `wazuh-logtest` confirmed:
```
Phase 2: decoder name: 'web-accesslog'
Phase 3: rule id: '100010' — Alert to be generated.
```

### 2.4 — Second obstacle: password/credential drift breaking the pipeline

After the rule was confirmed valid via `wazuh-logtest`, **live traffic still failed to appear in the dashboard** despite rule `100011` visibly firing in `/var/ossec/logs/alerts/alerts.json` with `firedtimes` incrementing correctly.

Root cause: Filebeat's output configuration (`/etc/filebeat/filebeat.yml`) still referenced the **old** OpenSearch admin password. Earlier in the session, the dashboard admin password had been reset (to troubleshoot an unrelated login issue) without updating Filebeat's stored credential — causing silent `401 Unauthorized` errors on every publish attempt. Alerts were being generated correctly by the manager but queued indefinitely at the Filebeat→Indexer hop, never reaching the searchable index.

**This is a realistic and valuable finding in its own right:** a detection pipeline can appear fully healthy at the rule-engine level while silently failing to deliver alerts to analysts, if a downstream credential goes stale. Verified via direct index query:
```bash
curl -k -u 'admin:admin' "https://localhost:9200/wazuh-alerts-4.x-*/_search?q=rule.id:100011"
# "total": 0   <- confirms alerts were NOT reaching the index, despite existing in alerts.json
```
After correcting the password in `filebeat.yml` and restarting the service:
```bash
curl -k -u 'admin:admin' "https://localhost:9200/wazuh-alerts-4.x-*/_search?q=rule.id:100011"
# "total": 6   <- confirmed fix
```

## 3. Final Verification

With both the log-source issue (2.1) and the credential-drift issue (2.4) resolved, generating 5 rapid requests to `profile.php?id=1..5` produced:
1. Five `rule.id: 100010` base events (level 3) — one per request
2. One `rule.id: 100011` correlation alert (level 10) — fired once the 5th request landed within the 60-second window, correctly citing the four preceding requests in its `previous_output` field

This confirms both rules function as designed, end-to-end, from raw Apache log through to searchable dashboard alert.

## 4. Lessons Learned

1. **Detection engineering is mostly plumbing, not rule syntax.** Of the total effort in this exercise, writing the XML rules themselves was the smallest part. Finding the correct log file, opening the correct firewall port, and keeping credentials synchronized across components consumed most of the time — this mirrors real SOC environments where "the rule doesn't fire" is far more often a data-pipeline problem than a logic problem.
2. **Correlation-based rules (frequency + timeframe) generalize well.** The same pattern used by Wazuh's built-in `5551` (PAM brute-force correlation, see this report) was directly reusable for an entirely different technique (IDOR enumeration) by simply changing the base rule and MITRE mapping — this is a reusable template for future custom rules (e.g., BOLA rule 100013, SSRF rule 100014 already written using the same pattern).
3. **A "healthy" pipeline still needs periodic credential verification.** Any time a shared secret (like the indexer admin password) is rotated, every downstream consumer of that credential (Filebeat, dashboard, any future SOAR integration) must be updated in lockstep, or alerts fail silently rather than loudly.

## 5. Detection Coverage Contribution

| Technique (MITRE) | Attack Type | Detected (default) | Detected (custom rule) | Correlated | Investigated |
|---|---|---|---|---|---|
| T1213 | IDOR — profile enumeration | ❌ | ✅ (rule 100010/100011) | ✅ | ✅ |

**Next planned exercise:** apply the same validated pipeline (log source + credentials confirmed working) to verify rules 100013 (BOLA) and 100014 (SSRF), which were written alongside 100010/100011 but not yet independently retested against live traffic.
