# Security Policy

## Supported Versions

UnoPim follows Laravel's release cadence. Each minor receives bug
fixes and security fixes from its release date until the underlying
Laravel version reaches End of Life.

| Version | PHP required | PHP tested | Laravel | Release             | Bug Fixes Until      | Security Fixes Until |
| ------- | ------------ | ---------- | ------- | ------------------- | -------------------- | -------------------- |
| 3.1     | 8.4.1+       | 8.4        | 13.x    | August 27th, 2026   | TBD                  | TBD                  |
| 3.0     | 8.4.1+       | 8.4        | 13.x    | July 31st, 2026     | August 27th, 2026    | TBD                  |
| 2.1     | 8.3+         | 8.3        | 12.x    | May 13th, 2026      | August 13th, 2026    | February 24th, 2027  |
| 2.0     | 8.3+         | 8.3        | 12.x    | March 27th, 2026    | August 13th, 2026    | February 24th, 2027  |

**Current:** 3.1.x

## End of Life

These versions no longer receive any patches. Upgrade to a supported
version.

| Version | PHP       | Laravel | Release             | End of Life          |
| ------- | --------- | ------- | ------------------- | -------------------- |
| 1.0     | 8.2 - 8.3 | 10.x    | November 21st, 2025 | May 26th, 2026       |

---

## Reporting a Vulnerability

Report security issues privately. Do **not** open a public GitHub
issue.

**Email** — [support@webkul.com](mailto:support@webkul.com).

Please include:

- A description of the issue
- Steps to reproduce (and a PoC, if available)
- Impact assessment (CVSS 4.0 or 3.1 vector welcome)
- Affected version(s)
- A suggested fix, if any

We acknowledge receipt within **72 hours**.

---

## Security Process

For every **supported** line (any row in the Supported Versions table
whose Security Fixes Until date is in the future), the flow is:

1. **Acknowledgement** within 72 hours
2. **Triage** — reproduction and severity assessment
3. **Fix development** on `master`, then backport to every supported
   line
4. **Coordinated patched releases** for each supported line, published
   on the same day
5. **GitHub Security Advisory** published with the reporter credited
   (when the reporter consents)

We aim to ship patches within **14 days** of acknowledgement for high
or critical severity, and **30 days** for medium severity.

---

## Preferred Language

Reports in **English** are easiest for our team to triage quickly.

---

## Thank You

Responsible disclosure keeps UnoPim and its users safe. Thank you for
taking the time to report issues to us privately.
