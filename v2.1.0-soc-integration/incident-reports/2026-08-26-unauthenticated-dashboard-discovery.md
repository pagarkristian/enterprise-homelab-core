# Unauthenticated Dashboard Access — Discovered During Verification

## Summary
| Field | Detail |
|---|---|
| **Vulnerability** | VULN-05 — Missing session/auth check on `dashboard.php` |
| **Discovery method** | **Incidental** — found while manually verifying the VULN-01 (IDOR) hardening fix, not part of the original TechSecure v2.0 design |
| **Affected asset** | `ubuntu-VirtualBox` (10.216.27.100) — TechSecure portal |
| **Affected endpoint** | `GET /dashboard.php` |
| **MITRE ATT&CK** | T1190 (Exploit Public-Facing Application) |
| **OWASP** | A01:2021 Broken Access Control |
| **Analyst** | Claudetier2 |
| **Status** | Closed — Hardening applied |

---

## 1. Discovery

While testing whether the VULN-01 IDOR fix worked correctly (verifying that `profile.php?id=2` now correctly returned "Forbidden" for a non-owning user), it was noticed that navigating directly to:
```
http://10.216.27.100:8080/dashboard.php
```
**loaded successfully even in a fresh browser session with no prior login.** This was not something the original TechSecure v2.0 design document anticipated — `dashboard.php` is inherited unchanged from the v1.x codebase, and it was assumed (incorrectly) that access to it was already gated by the login flow.

## 2. Root Cause

Inspection of `dashboard.php` confirmed it had **no `session_start()` call and no authentication check whatsoever** at the top of the file. The page only *displayed* a username if a session happened to exist (`$_SESSION['username'] ?? ''` would simply render blank) — it never verified one was required to view the page's content or use its file-upload functionality.

This is a distinct instance of the same **Broken Access Control** category as VULN-01, but at a different layer: VULN-01 was an *object-level* authorization gap in an API (logged-in user, wrong object). VULN-05 is a *page-level* authentication gap (no login required at all).

## 3. Why This Matters for a Portfolio

This finding is arguably more valuable than the planned vulnerabilities, because it demonstrates a skill that can't be scripted in advance: **noticing an anomaly during unrelated verification work and following up on it**, rather than only checking the specific things a test plan says to check. Real security testing regularly surfaces findings this way — a tester exploring one issue stumbles onto an unrelated one nearby.

## 4. Hardening Applied

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /index.php");
    exit();
}
?>
```
Added to the very top of `dashboard.php`, before any HTML output. Also took the opportunity to display the logged-in username in the navbar (`$_SESSION['username']`) and add navigation links to the new v2.0 API features (profile view, avatar import, report download) — giving the newly-added API endpoints a discoverable entry point from the UI, rather than existing as "orphan" endpoints only reachable by guessing URLs directly.

## 5. Verification

| Test | Before fix | After fix |
|---|---|---|
| Access `/dashboard.php` with no active session | Page loads normally, upload form fully functional | Redirected to `/index.php` (login page) |
| Access `/dashboard.php` after valid login | Page loads normally | Page loads normally, now shows username + v2.0 feature links |

## 6. Detection Coverage Contribution

| Technique (MITRE) | Attack Type | Detected | Hardened |
|---|---|---|---|
| T1190 | Unauthenticated dashboard access | ⬜ Not yet — no custom rule written for this specific gap | ✅ |

**Note on detection:** unlike VULN-01 (this report), no custom Wazuh rule was written for this vulnerability, since it was caught and fixed directly during hardening rather than being run through the full Attack → Gap Analysis → Detection cycle. A future exercise could add a rule that flags direct `GET /dashboard.php` access with no preceding `POST /index.php` (login) from the same source within a reasonable window — a useful pattern for catching authentication-bypass attempts in general, not just this specific page.
