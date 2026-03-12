---
name: unopim-dev-cycle
description: "Development workflow for UnoPim. Activates when running tests, linting code, building assets, or debugging; or when the user mentions test, lint, pint, build, npm, pest, format, style, quality, coverage, or needs to verify code works."
license: MIT
metadata:
  author: unopim
---

# UnoPim Development Cycle

This skill provides guidance for the UnoPim development workflow, including running tests, code quality checks, building assets, and troubleshooting.

## Instructions

Follow these guidelines for UnoPim development workflow:

1. **Running tests**: See [running-tests.md](running-tests.md) for Pest test commands and setup
2. **Code quality**: See [code-quality.md](code-quality.md) for linting and style fixes
3. **Building assets**: See [building-assets.md](building-assets.md) for Vite and npm workflows

## Development Workflow

The standard development workflow:

1. Make code changes
2. Run code formatter: `./vendor/bin/pint`
3. Run relevant tests: `./vendor/bin/pest --filter YourTest`
4. Build assets if frontend changed: `npm run build`
5. Clear caches: `php artisan optimize:clear`
6. Commit changes only after tests pass

## Quick Reference

```bash
# Format PHP code
./vendor/bin/pint

# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest --testsuite="Admin Feature Test"

# Build frontend assets
npm run build

# Clear all caches
php artisan optimize:clear

# Start dev server
php artisan serve

# Start queue worker (for import/export)
php artisan queue:work --queue="default,system"
```

## Mandatory CI Verification (HARD REQUIREMENT)

Before considering ANY feature or fix complete, you MUST run and pass ALL applicable checks. GitHub Actions will fail if these are skipped.

### Step 1: Pint (PHP Code Style)

```bash
# Auto-fix style on changed files
./vendor/bin/pint --dirty

# Verify no violations remain (dry run)
./vendor/bin/pint --test --dirty
```

See [code-quality.md](code-quality.md) for common Pint pitfalls.

### Step 2: Pest (PHP Tests)

```bash
# Run tests for the package you changed
./vendor/bin/pest packages/Webkul/{Package}/tests/

# Run specific test file
./vendor/bin/pest path/to/YourTest.php
```

See [running-tests.md](running-tests.md) for common Pest pitfalls.

### Step 3: Playwright Sync Check (E2E Tests)

Playwright tests (`tests/e2e-pw/`) run in CI but not locally. You MUST keep them in sync when changing:

- **Translations** (`Resources/lang/*/app.php`): Search `tests/e2e-pw/` for the old text and update assertions
- **Form fields or UI components**: Search `tests/e2e-pw/` for affected selectors
- **Import/export job states or flow**: Check `tests/e2e-pw/tests/04-datatransfer/`
- **Never use dynamic Vue `$.uid`-based IDs** in tests (e.g., `id="36_dropzone-file"`) — use stable selectors like `input[type="file"][name="file"]`

```bash
# Search Playwright tests for text you changed
grep -r "OLD TEXT" tests/e2e-pw/
```

## Key Principles

- Always run `./vendor/bin/pint` before committing PHP changes
- Run relevant tests after making changes
- Keep Playwright tests in sync with translations and UI changes
- Build assets after modifying Vue/CSS/JS files
- Clear caches after config or route changes
- Queue workers must be running for import/export operations
