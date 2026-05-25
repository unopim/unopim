# v1.0.x

## v1.0.1 - 2026-05-25

### Security
- Patched an authorization gap on several admin write-verb routes (`*.store` / `*.update`) — they were not present in `packages/Webkul/Admin/src/Config/acl.php`, so the `Bouncer` middleware never enforced a permission check. Low-privileged admins could submit write requests to catalog, settings, and data-transfer endpoints they could not legitimately view. Mapped each missing route to the same ACL key as its sibling GET form (`.create` / `.edit`) and added regression coverage. Also corrected the response status code from `401` to `403` on permission-denied responses (user is authenticated, just unauthorized).

## v1.0.0

Initial 1.0 release.
