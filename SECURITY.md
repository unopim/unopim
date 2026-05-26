# Security Policy

## Supported Versions

UnoPim follows the same release cadence as Laravel: each minor receives
**18 months of bug fixes** and **24 months of security fixes** from its
release date.

| Version | PHP (*)   | Release             | Bug Fixes Until         | Security Fixes Until    |
| ------- | --------- | ------------------- | ----------------------- | ----------------------- |
| 1.0     | 8.2 - 8.3 | November 21st, 2025 | May 26th, 2026          | May 26th, 2026          |
| 2.0     | 8.3 - 8.4 | March 27th, 2026    | September 27th, 2027    | March 27th, 2028        |
| 2.1     | 8.3 - 8.4 | May 13th, 2026      | November 13th, 2027     | May 13th, 2028          |
| 2.2     | 8.3 - 8.4 | Q3 2026             | Q1 2028                 | Q3 2028                 |

**Current:** 2.1.x

> (*) The PHP range lists the minimum required PHP version and the
> highest stable PHP version verified against the branch's CI matrix.
>
> Versions whose **Security Fixes Until** date has passed receive no
> further patches. The **1.0** line was retired ahead of the standard
> 24-month window because it predates the Laravel 11 bootstrap rewrite
> and the per-branch backport cost exceeded the user base it covered;
> `v1.0.1` is the final release on `1.0`.

---

## Reporting a Vulnerability

Report security issues privately. Do **not** open a public GitHub
issue.

Preferred channels (either works):

1. **GitHub Security Advisories** — the "Report a vulnerability"
   button on the [Security tab](https://github.com/unopim/unopim/security)
   of this repository.
2. **Email** — [support@webkul.com](mailto:support@webkul.com).

Please include:

- A description of the issue
- Steps to reproduce (and a PoC, if available)
- Impact assessment (CVSS 3.1 vector welcome)
- Affected version(s)
- A suggested fix, if any

We acknowledge receipt within **72 hours**.

---

## Security Process

For every **supported** line (any row above whose Security Fixes Until
date is in the future), the flow is:

1. **Acknowledgement** within 72 hours
2. **Triage** — reproduction and CVSS 3.1 severity assessment
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
