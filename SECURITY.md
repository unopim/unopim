# Security Policy

## Supported Versions

The `1.0` line reached End of Life on **May 26th, 2026**. Upgrade to
the latest supported version of UnoPim (currently `2.1.x`) for
ongoing bug fixes and security patches.

## End of Life

These versions no longer receive any patches.

| Version | PHP       | Laravel | Release             | End of Life          |
| ------- | --------- | ------- | ------------------- | -------------------- |
| 1.0     | 8.2 - 8.3 | 10.x    | November 21st, 2025 | May 26th, 2026       |

> `v1.0.1` is the final release on this line. The 1.0 branch predates
> Laravel 11's `Application::configure()` bootstrap and the per-branch
> backport cost grew large enough that we retired the line ahead of
> Laravel 10's own EOL window.

---

## Reporting a Vulnerability

Report security issues against **supported** versions privately. Do
**not** open a public GitHub issue.

Preferred channels (either works):

1. **GitHub Security Advisories** — the "Report a vulnerability"
   button on the [Security tab](https://github.com/unopim/unopim/security)
   of this repository.
2. **Email** — [support@webkul.com](mailto:support@webkul.com).

Vulnerabilities specific to `1.0.x` will not be patched. Please verify
the issue still reproduces on a supported version before reporting.

We acknowledge receipt within **72 hours**.

---

## Preferred Language

Reports in **English** are easiest for our team to triage quickly.

---

## Thank You

Responsible disclosure keeps UnoPim and its users safe.
