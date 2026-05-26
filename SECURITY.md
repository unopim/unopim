# Security Policy

## Supported Versions

UnoPim follows a rolling support policy: the latest minor receives active
maintenance, the previous minor receives security patches only, and older
lines are End of Life.

| Version | Status              | Security Fixes | End of Life   |
| ------- | ------------------- | -------------- | ------------- |
| 2.1.x   | ✅ Active            | ✅              | TBD           |
| 2.0.x   | 🛡 Security-only    | ✅              | ~2026-Q4 *    |
| 1.0.x   | ❌ End of Life       | ❌              | **2026-05-26** (final release `v1.0.1`) |
| 0.3.x   | ❌ End of Life       | ❌              | 2025          |
| 0.2.x   | ❌ End of Life       | ❌              | 2025          |

> \* `2.0.x` will move to End of Life ~90 days after `2.2.0` ships.
>
> ⚠️ Versions marked **End of Life** receive no further patches.
> Upgrade to `2.1.x` to stay covered.

### Why `1.0.x` is End of Life

`v1.0.1` is the **final** release on the `1.0` line. The branch is on
Laravel 10 (EOL 2026-08), the bootstrap layer pre-dates Laravel 11's
`Application::configure()` API, and the per-branch maintenance cost
has grown too high to justify continued backports. The two
vulnerabilities patched in `v1.0.1` are the last security fixes that
will ship for 1.0.

---

## Reporting a Vulnerability

If you discover a security vulnerability, report it privately:

1. **Do not open a public GitHub issue.**
2. **Email:** [support@webkul.com](mailto:support@webkul.com), or use
   GitHub Security Advisories ("Report a vulnerability" tab on this
   repository).

Include:
- Description of the issue
- Steps to reproduce
- Impact assessment
- Affected version(s)
- Suggested fix (optional)

We acknowledge receipt within **72 hours**.

---

## Security Process

For **supported** versions (`2.1.x`, `2.0.x` security-only), the flow is:

1. **Acknowledgement** within 72 hours
2. **Investigation & severity assessment** (CVSS 3.1)
3. **Fix development** on `master`, then backport to every supported line
4. **Coordinated patched releases** for each supported line
5. **GitHub Security Advisory** with credit (if the reporter consents)

---

## Preferred Language

Please report vulnerabilities in **English**.

---

## Thank You

Responsible disclosure helps keep UnoPim and its users safe. We are
grateful for your time.
