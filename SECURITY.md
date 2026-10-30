# Security Policy

## Supported Versions

UnoPim follows Laravel's release cadence. Only the 3.x line, built on
Laravel 13, receives security fixes; earlier lines are End of Life
and users should upgrade to the latest 3.x release.

| Version | PHP required | PHP tested | Laravel | Release             | Bug Fixes Until      | Security Fixes Until |
| ------- | ------------ | ---------- | ------- | ------------------- | -------------------- | -------------------- |
| 3.1     | 8.4.1+       | 8.4        | 13.x    | August 27th, 2026   | TBD                  | TBD                  |
| 3.0     | 8.4.1+       | 8.4        | 13.x    | July 31st, 2026     | August 27th, 2026    | TBD                  |

**Current:** 3.1.x

## End of Life

These versions no longer receive any patches. Upgrade to a supported
version.

| Version | PHP       | Laravel | Release             | End of Life          |
| ------- | --------- | ------- | ------------------- | -------------------- |
| 2.1     | 8.3+      | 12.x    | May 13th, 2026      | October 30th, 2026   |
| 2.0     | 8.3+      | 12.x    | March 27th, 2026    | October 30th, 2026   |
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
3. **Fix development** on `3.x`, the current release line, then
   ported to `master` and backported to every other supported line
4. **Coordinated patched releases** for each supported line, published
   on the same day
5. **GitHub Security Advisory** published with the reporter credited
   (when the reporter consents)

We aim to ship patches within **14 days** of acknowledgement for high
or critical severity, and **30 days** for medium severity.

---

## Published Advisories

Fixed vulnerabilities are announced as
[GitHub Security Advisories](https://github.com/unopim/unopim/security/advisories)
and listed in the [changelog](CHANGELOG.md) of the release that fixes
them.

| Advisory                                                                         | CVE            | Severity | Fixed in |
| -------------------------------------------------------------------------------- | -------------- | -------- | -------- |
| [GHSA-8p2f-fx4q-75cx](https://github.com/unopim/unopim/security/advisories/GHSA-8p2f-fx4q-75cx) | CVE-2025-55741 | High     | 0.3.1    |
| [GHSA-74rg-6f92-g6wx](https://github.com/unopim/unopim/security/advisories/GHSA-74rg-6f92-g6wx) | CVE-2025-55745 | High     | 0.3.1    |
| [GHSA-287x-6r2h-f9mw](https://github.com/unopim/unopim/security/advisories/GHSA-287x-6r2h-f9mw) | CVE-2025-55744 | Medium   | 0.2.1    |
| [GHSA-v22v-xwh7-2vrm](https://github.com/unopim/unopim/security/advisories/GHSA-v22v-xwh7-2vrm) | CVE-2025-55743 | Critical | 0.2.1    |
| [GHSA-xr97-25v7-hc2q](https://github.com/unopim/unopim/security/advisories/GHSA-xr97-25v7-hc2q) | CVE-2025-55742 | High     | 0.2.1    |
| [GHSA-cgr4-c233-h733](https://github.com/unopim/unopim/security/advisories/GHSA-cgr4-c233-h733) | CVE-2024-50637 | Low      | 0.1.4    |

---

## Preferred Language

Reports in **English** are easiest for our team to triage quickly.

---

## Thank You

Responsible disclosure keeps UnoPim and its users safe. Thank you for
taking the time to report issues to us privately.
