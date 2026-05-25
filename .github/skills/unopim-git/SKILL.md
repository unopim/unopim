---
name: unopim-git
description: "Git and GitHub operations for UnoPim. Activates when creating branches, writing commit messages, or creating pull requests; or when the user mentions git, branch, commit, PR, pull request, merge, or changelog."
license: MIT
metadata:
  author: unopim
---

# UnoPim Git Guidelines

## Branch Naming

```
fix/issue-1234-short-description
feature/short-description
refactor/short-description
test/short-description
```

- Separate each fix into a new branch named with the issue ID: `issue-1234`
- Use lowercase with hyphens

## Commit Messages

```
Fixed #1234 - Short description of the change

Optional longer description explaining the change in more detail.
```

- Reference issue number in commit: `Fixed #1234 - description`
- Use imperative mood: "Fix bug" not "Fixed bug" (except for issue references)
- Keep subject line under 72 characters
- Separate subject from body with a blank line

## Pull Requests

When creating PRs:

1. Follow the template at `.github/PULL_REQUEST_TEMPLATE.md`
2. Include all sections from the template
3. Reference the issue being fixed
4. Describe testing performed
5. Include screenshots for UI changes

### PR Body via CLI

```bash
gh pr create --title "Fixed #1234 - Description" --body "$(cat <<'EOF'
## Description
Brief description of changes.

## How to test
1. Step one
2. Step two

## Screenshots
N/A
EOF
)"
```

## Pre-Push CI Verification (MANDATORY)

Before pushing ANY branch or creating a PR, you MUST verify these pass. GitHub Actions runs Pint, Pest, and Playwright — all three must pass.

```bash
# 1. Fix and verify PHP code style
./vendor/bin/pint --dirty
./vendor/bin/pint --test --dirty

# 2. Run Pest tests for changed packages
./vendor/bin/pest packages/Webkul/{Package}/tests/

# 3. If translations or UI changed, verify Playwright test compatibility
grep -r "CHANGED_TEXT" tests/e2e-pw/
```

See the `unopim-dev-cycle` skill for detailed CI pitfalls and common failure patterns.

## Contributing Guidelines

See `.github/CONTRIBUTING.md` for full contribution process:

- Bug reports: Search existing issues first, then open new
- Bug fixes: Fork repo, create branch, submit PR
- Features: Create feature request issue first, then implement
- Follow PR template as much as possible
