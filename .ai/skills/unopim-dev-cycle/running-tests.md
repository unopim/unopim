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

### Running E2E tests (Playwright)

```bash
cd tests/e2e-pw
npx playwright test
```
