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

## Pre-Commit Checklist

Before committing any changes:

1. **PHP formatting**: `./vendor/bin/pint`
2. **Run tests**: `./vendor/bin/pest --filter YourTest`
3. **Build assets** (if frontend changed): `npm run build`
4. **Clear caches**: `php artisan optimize:clear`

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
