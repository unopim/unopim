# Running Tests — UnoPim

UnoPim uses **Pest** (built on PHPUnit) for testing.

---

## Test Commands

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suite
./vendor/bin/pest --testsuite="Admin Feature Test"
./vendor/bin/pest --testsuite="Api Feature Test"
./vendor/bin/pest --testsuite="Core Unit Test"
./vendor/bin/pest --testsuite="DataGrid Unit Test"
./vendor/bin/pest --testsuite="DataTransfer Unit Test"
./vendor/bin/pest --testsuite="User Feature Test"
./vendor/bin/pest --testsuite="ElasticSearch Feature Test"
./vendor/bin/pest --testsuite="Completeness Feature Test"

# Run specific test file
./vendor/bin/pest packages/Webkul/Admin/tests/Feature/Catalog/ProductTest.php

# Filter by test name
./vendor/bin/pest --filter="it_can_create_a_product"

# Run with coverage report
./vendor/bin/pest --coverage

# Run tests in parallel
./vendor/bin/pest --parallel

# Stop on first failure
./vendor/bin/pest --stop-on-failure
```

---

## Test Environment Configuration

From `phpunit.xml`:

| Setting | Value |
|---|---|
| `APP_ENV` | `testing` |
| `APP_DEBUG` | `true` |
| `BCRYPT_ROUNDS` | `4` (faster tests) |
| `CACHE_DRIVER` | `array` |
| `MAIL_MAILER` | `array` |
| `QUEUE_CONNECTION` | `sync` |
| `SESSION_DRIVER` | `array` |

---

## Test Directories

| Suite | Test Location |
|---|---|
| Admin Feature | `packages/Webkul/Admin/tests/Feature/` |
| Admin API Feature | `packages/Webkul/AdminApi/tests/Feature/` |
| Core Unit | `packages/Webkul/Core/tests/Unit/` |
| DataGrid Unit | `packages/Webkul/DataGrid/tests/Unit/` |
| DataTransfer Unit | `packages/Webkul/DataTransfer/tests/Unit/` |
| User Feature | `packages/Webkul/User/tests/Feature/` |
| Installer Feature | `packages/Webkul/Installer/tests/Feature/` |
| ElasticSearch Feature | `packages/Webkul/ElasticSearch/tests/Feature/` |
| Completeness Feature | `packages/Webkul/Completeness/tests/Feature/` |
| E2E (Playwright) | `tests/e2e-pw/` |

---

## Troubleshooting

### Tests fail with database errors

Ensure SQLite is available (tests use in-memory SQLite by default) or configure `.env.testing`:

```bash
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Tests fail with "class not found"

```bash
composer dump-autoload
```

### Tests hang

Check `QUEUE_CONNECTION=sync` is set in test environment.

### Common Pest Pitfalls (CI Failures)

| Pitfall | Example | Fix |
|---|---|---|
| Timezone assumptions | `Carbon::parse($date)->toJson()` gives different results on CI vs local | Always pass explicit timezone: `Carbon::parse($date, 'UTC')` when comparing against UTC-formatted strings |
| Hardcoded dynamic IDs | Test expects `id="36_dropzone-file"` | Use stable selectors — Vue `$.uid` changes between runs |
| Environment-dependent values | Test assumes specific locale or config | Use explicit config/locale setup in test `beforeEach` |

### Running E2E Tests (Playwright)

```bash
cd tests/e2e-pw
npx playwright test
```

### Keeping Playwright Tests in Sync (CRITICAL)

Playwright tests run in GitHub Actions CI. They cannot easily be run locally, so you MUST manually verify compatibility when changing:

**Translation changes** (`packages/Webkul/Admin/src/Resources/lang/*/app.php`):
```bash
# Find Playwright tests that reference the old translation text
grep -r "OLD TEXT" tests/e2e-pw/
# Update all matching assertions to use the new text
```

**UI/form changes** (blade templates, Vue components):
```bash
# Find Playwright tests that use selectors from the changed component
grep -r "selector-or-text" tests/e2e-pw/
```

**Import/export flow changes**:
- Check `tests/e2e-pw/tests/04-datatransfer/import.spec.js`
- Check `tests/e2e-pw/tests/04-datatransfer/export.spec.js`

**Selector best practices for Playwright tests:**
- Use role-based selectors: `getByRole('button', { name: 'Save' })`
- Use text-based selectors: `getByText(/success/i)`
- Use name attributes: `input[type="file"][name="file"]`
- NEVER use dynamic Vue `$.uid`-based IDs (e.g., `id="36_dropzone-file"`) — these change between page loads
