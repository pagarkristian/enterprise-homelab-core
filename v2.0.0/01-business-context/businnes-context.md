# 🏢 Enterprise Homelab Core v2.0.0: Business Context

<div align="center">
  <img src="https://img.shields.io/badge/Phase-Business_Context-1F4068?style=for-the-badge&logo=briefcase&logoColor=white" alt="Phase" />
  <img src="https://img.shields.io/badge/Methodology-Purple_Team-8E44AD?style=for-the-badge&logo=target&logoColor=white" alt="Methodology" />
  <img src="https://img.shields.io/badge/Status-In_Progress-D29922?style=for-the-badge&logo=progress&logoColor=white" alt="Status" />
</div>

<div align="center"><sub>From Infrastructure Hardening to Security Monitoring</sub></div>

---

## 🌟 1. The Situation

TechSecure Indonesia's infrastructure cleared its security audit in **v1.1.0**. Firewall rules locked down, SSH hardened, SQL injection patched. On paper, the server was done.

Two weeks later, two requests landed on the admin's desk — neither from security, both from people just trying to get their jobs done faster.

---

## 📋 2. Business Requests Matrix

<div align="center">

<table>
<tr>
<th align="center" width="18%">Requested By</th>
<th align="center" width="27%">What They Asked For</th>
<th align="center" width="27%">Why It Matters to Them</th>
<th align="center" width="28%">What It Opens Up</th>
</tr>
<tr>
<td align="center">
<img src="https://img.shields.io/badge/HR-Department-2E7D32?style=flat-square" /><br><br>📸
</td>
<td>Let employees upload their own profile photo, auto-resized by the system</td>
<td>40+ staff and growing — manual upload by HR doesn't scale anymore</td>
<td>First new file-processing logic since the v1.1.0 upload fix — the file isn't just <i>stored</i> now, it's <i>processed</i></td>
</tr>
<tr>
<td align="center">
<img src="https://img.shields.io/badge/Supervisors-Operations-1565C0?style=flat-square" /><br><br>💰
</td>
<td>Direct links to payroll slips — <code>slip.php?id=5</code></td>
<td>Skip the login → navigate → search routine just to forward one document</td>
<td>One changed digit in the URL is the difference between "my payslip" and "anyone's payslip"</td>
</tr>
</table>

</div>

---

## 🔍 3. The Real Question

Both features are low-risk from a business standpoint and high-value for the people asking. Nobody's going to say no to either one.

<div align="center">

<table>
<tr>
<td align="center">
<h3>💭 "Once these features are deployed — will anyone notice if something goes wrong?"</h3>
</td>
</tr>
</table>

</div>

v1.1.0 proved the server could survive attacks that were already known. v2.0.0 asks something harder: build what the business actually wants, deliberately wire in the mistakes a developer might make under deadline pressure, and find out whether a SIEM watching the server would catch them.

---

## 🛡️ 4. What This Phase Evaluates

<div align="center">

<table>
<tr>
<th align="center">Area</th>
<th align="center">What's Being Tested</th>
</tr>
<tr>
<td align="center"><img src="https://img.shields.io/badge/📥-Log_Collection-0052CC?style=flat-square" /></td>
<td align="center">Are the right logs even reaching the SIEM?</td>
</tr>
<tr>
<td align="center"><img src="https://img.shields.io/badge/🚨-Detection-D32F2F?style=flat-square" /></td>
<td align="center">Do default rules catch these specific attacks?</td>
</tr>
<tr>
<td align="center"><img src="https://img.shields.io/badge/🔎-Investigation-8E44AD?style=flat-square" /></td>
<td align="center">Can the alert be traced back to a clear root cause?</td>
</tr>
<tr>
<td align="center"><img src="https://img.shields.io/badge/🔧-Remediation-E95420?style=flat-square" /></td>
<td align="center">Once found, how is the gap actually closed?</td>
</tr>
<tr>
<td align="center"><img src="https://img.shields.io/badge/🔄-Retesting-1F883D?style=flat-square" /></td>
<td align="center">Does the fix hold up against the same attack again?</td>
</tr>
</table>

</div>

---

## 🔄 5. Project Flow

```diff
+[Hardened Infrastructure] ──> [Feature Development] ──> [Wazuh SIEM Deployed]
 (v1.1.0 baseline)             (intentionally imperfect)
       │
       ▼
+[Attack Simulation] ──> [Detection Engineering] ──> [Retesting & Report]
 (from Kali node)          (rules for what defaults missed)
```

---

<div align="center">
  <sub>Enterprise Homelab Core v2.0.0 — Business Context · Maintained by <b>pagarkristian</b></sub>
</div>
