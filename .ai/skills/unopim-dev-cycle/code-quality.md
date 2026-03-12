# Code Quality — UnoPim

---

## PHP Linting & Formatting

UnoPim uses **Laravel Pint** with the `laravel` preset.

```bash
# Check for style violations (dry run)
./vendor/bin/pint --test

# Fix all style issues
./vendor/bin/pint

# Fix only changed files (recommended before commits)
./vendor/bin/pint --dirty

# Fix specific file
./vendor/bin/pint path/to/File.php

# Fix specific directory
./vendor/bin/pint packages/Webkul/Product/src/
```

### Configuration

`pint.json` at project root:

```json
{
    "preset": "laravel",
    "rules": {
        "binary_operator_spaces": {
            "operators": {
                "=>": "align"
            }
        }
    }
}
```

---

## Common Pint Pitfalls (CI Failures)

These are the most frequent Pint violations that cause GitHub Actions to fail:

| Violation | Example | Fix |
|---|---|---|
| Extra alignment spaces | `$var    = value;` | Only `=>` alignment is allowed (per `pint.json`). Use single space for `=` |
| Unused closure `use` vars | `function () use ($unused) {` | Remove variables not referenced inside the closure body |
| Missing/extra blank lines | Extra blank line between methods | Follow PSR-12 spacing rules |
| Trailing whitespace | Spaces at end of line | Trim trailing whitespace |

**Important:** `pint.json` only allows `=>` alignment (`"binary_operator_spaces": {"operators": {"=>": "align"}}`). All other operators (`=`, `??=`, etc.) must use single spaces.

## Pre-Commit Checklist

Before committing any changes:

1. **PHP formatting**: `./vendor/bin/pint --dirty` (auto-fix) then `./vendor/bin/pint --test --dirty` (verify)
2. **Run tests**: `./vendor/bin/pest packages/Webkul/{Package}/tests/`
3. **Playwright sync**: If translations or UI changed, search `tests/e2e-pw/` for affected text/selectors
4. **Build assets** (if frontend changed): `npm run build`
5. **Clear caches**: `php artisan optimize:clear`

---

## Static Analysis

For deeper analysis, consider running:

```bash
# PHPStan (if configured)
./vendor/bin/phpstan analyse packages/Webkul/{Package}/src/

# Larastan (Laravel-specific)
./vendor/bin/phpstan analyse --level=5
```

---

## Editor Configuration

`.editorconfig` enforces:

| Setting | Value |
|---|---|
| Charset | UTF-8 |
| Line endings | LF |
| Indent size | 4 spaces |
| Indent style | Spaces |
| Final newline | Yes |
| Trailing whitespace | Trimmed |
| YAML indent | 2 spaces |
| Markdown trailing whitespace | Preserved |
