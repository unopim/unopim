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

## Key Principles

- Always run `./vendor/bin/pint` before committing PHP changes
- Run relevant tests after making changes
- Build assets after modifying Vue/CSS/JS files
- Clear caches after config or route changes
- Queue workers must be running for import/export operations
