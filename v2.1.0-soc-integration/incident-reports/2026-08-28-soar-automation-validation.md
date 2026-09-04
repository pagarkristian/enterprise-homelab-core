# SOAR Automation Validation — Real-Time Alerting and a Rule Hierarchy Bug

## Summary
| Field | Detail |
|---|---|
| **Milestone** | Level 10 — SOAR Automation, first fully validated end-to-end run |
| **Component built** | Custom Wazuh Integration script (`custom-telegram.py`) |
| **Trigger rule** | 100011 (TechSecure IDOR correlation, level 10) |
| **Delivery channel** | Telegram Bot API |
| **Analyst** | Claudetier2 |
| **Status** | Closed — automation validated working end-to-end |

---

## 1. Objective

Move beyond manual dashboard checking (Level 5-9 triage) to **automated, real-time alerting**: when a high-severity custom rule fires, an analyst should be notified immediately via a channel they already monitor (Telegram), without needing to have the Wazuh dashboard open.

## 2. Build

Rather than using a no-code SOAR platform (Shuffle/n8n, as originally planned in the roadmap), a **custom Python bot** was already in progress independently (`bot.py`, built with `python-telegram-bot`) as a Telegram command bot (`/start`, `/status`). This was leveraged as the foundation, extended with a second, purpose-built script using **Wazuh's native Integration Framework** — the same mechanism Wazuh uses for its official Slack/PagerDuty/VirusTotal integrations.

**Why the native integration framework over a separate polling script:** it's event-driven (Wazuh calls the script the moment a matching alert fires) rather than requiring a separate process to continuously poll `alerts.json`, and it mirrors how this kind of integration is actually built in production SOC environments.

`custom-telegram.py` (placed in `/var/ossec/integrations/`) receives the alert file path as its first argument, parses the JSON, formats a message, and posts it via the Telegram Bot API using `requests`.

Registered in `ossec.conf`:
```xml
<integration>
  <name>custom-telegram.py</name>
  <level>10</level>
  <alert_format>json</alert_format>
</integration>
```
`<level>10</level>` scopes this to high-severity alerts only, avoiding notification fatigue from routine low-level events.

## 3. Debugging Process

### 3.1 — Script syntax error from a truncated paste
First test run failed with `SyntaxError: expected ':'` on the final line — a copy-paste truncation left `if __name__ == "__main__":` split across two lines as `if __name_` / `_ == "__main__":`. Fixed by directly editing the malformed line rather than re-pasting the whole file. A reminder that terminal paste operations, especially long multi-line scripts, warrant a full-file review before assuming success.

### 3.2 — `pip3` execution failure with a misleading error
Attempting `sudo /var/ossec/framework/python/bin/pip3 install requests` failed with `unable to execute ... No such file or directory` — despite the file visibly existing. Root cause: the file's shebang line pointed to a **stale path** (`/var/wazuh-manager/framework/python/bin/python3`) left over from an earlier Wazuh version/reinstall, which no longer existed. The fix was simply invoking the interpreter directly (`python3 -m pip install requests`) rather than relying on the shebang-dependent `pip3` wrapper. As it turned out, `requests` was already installed, so no further action was needed — but the debugging path (checking the shebang with `head -1`) is a generally useful technique for "file exists but won't execute" errors.

### 3.3 — First live test succeeded, but with the *wrong* rule
After registering the integration and generating IDOR test traffic, a Telegram alert **did** arrive — but for rule `31101` ("Multiple web server 400 error codes from same source ip"), a built-in Wazuh rule, not the custom `100011` rule this exercise was meant to validate. This confirmed the integration mechanism itself worked (any level-10 alert triggers it), but didn't yet prove the custom rule specifically was compatible with it.

### 3.4 — The core bug: rule hierarchy conflict with HTTP error codes

Repeated attempts to trigger rule `100011` specifically kept failing, even with request timing well within the `frequency=5, timeframe=60` window. Investigation with `wazuh-logtest` on a single 403-response log line revealed the cause:

```
Phase 3: Completed filtering (rules).
    id: '31101'
    level: '5'
    description: 'Web server 400 error code.'
```

Both the custom rule (`100010`, `<if_sid>31100</if_sid>`) and Wazuh's built-in 4xx-error rule (`31101`, also a child of `31100`) compete for the same underlying event. Wazuh's rule engine matches the **first** applicable rule in its evaluation order, and `31101` — being more specific to error codes — claimed any `403 Forbidden` response before the custom rule ever got a chance to fire. Since VULN-01 was already hardened at this point (correctly returning 403 for unauthorized profile access), **every single test request after hardening was silently being reclassified as a generic "4xx error" event instead of a "profile endpoint accessed" event** — breaking the correlation rule's frequency count entirely.

**Fix:** broadened the base rules' parent reference from a single ID to a comma-separated list covering both outcomes:
```xml
<rule id="100010" level="3">
  <if_sid>31100,31101</if_sid>
  <url>/api/v1/users/profile.php</url>
  ...
```
This was applied consistently across all three endpoint-tracking base rules (100010, 100012, 100014) so that post-hardening (403) traffic is captured identically to pre-hardening (200) traffic — an important property, since a SOC needs visibility into **attempted** access regardless of whether the attempt succeeded.

## 4. Final Validation

With the rule hierarchy fix applied, a scripted `curl` loop (5 authenticated requests to `profile.php?id=1..5` within 5 seconds) produced:
1. Alert `100011` (level 10) correctly fired in `alerts.json`
2. Within seconds, a fully formatted Telegram message arrived automatically, with no manual script invocation:
   ```
   🚨 SOC Alert — Level 10
   Description: TechSecure: Possible IDOR enumeration on user profile API (rapid repeated access)
   Rule ID: 100011
   Agent: ubuntu-VirtualBox
   Source IP: 10.216.27.50
   Time: 2026-08-28T07:06:46.019+0000
   ```

## 5. Why This Matters

1. **A vulnerability being "fixed" doesn't mean detection should go dark.** This exercise surfaced a subtle but important principle: once VULN-01 was hardened, the attack attempts started returning 403s instead of 200s — and the *original* detection rule silently stopped seeing them, because it only accounted for the success-path response code. A SOC that only detects successful exploitation, and goes blind to *attempted-but-blocked* exploitation, has an important coverage gap: it can no longer tell the difference between "nobody is attacking us" and "somebody is attacking us and failing" — the latter is still valuable threat intelligence (an active adversary probing the system).
2. **Rule ordering/hierarchy in a SIEM is a real operational concern**, not just an implementation detail. The same underlying log line can be legitimately claimed by multiple candidate rules, and understanding *which one wins* — and why — is necessary to avoid silent detection gaps like this one.
3. **This was found through methodical elimination**, not luck: confirming the integration mechanism worked (3.3) before assuming the custom rule itself was broken, then using `wazuh-logtest` to directly observe which rule matched a specific failing case, isolated the root cause precisely rather than guessing.

## 6. Detection Coverage Contribution

| Technique (MITRE) | Attack Type | Detected (pre-hardening) | Detected (post-hardening, blocked attempts) | Automated Alert Delivery |
|---|---|---|---|---|
| T1213 | IDOR enumeration (VULN-01) | ✅ | ✅ (fixed in this exercise — previously blind) | ✅ Telegram, real-time |

**Note:** the same `31100,31101` fix was proactively applied to rules 100012 (BOLA) and 100014 (SSRF) even though they were not independently re-tested in this exercise, since they share the identical structural vulnerability to the same rule-hierarchy issue. This is flagged in `SCORE.md` as still needing formal retest confirmation.
