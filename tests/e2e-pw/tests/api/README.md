# UnoPim Playwright — API Test Suite

End-to-end REST API automation for UnoPim 2.x using Playwright's
`APIRequestContext`. Every endpoint in the 2.1 public surface is exercised
with positive, negative, validation, pagination, filter, sorting, schema,
response-time and authorization tests.

## Folder layout

```
tests/api/                       # Spec files, one folder per resource
  01-oauth/                      #   ─ OAuth2 token issuance
  02-locales/
  03-currencies/
  04-channels/
  05-categories/
  06-category-fields/
  07-category-field-options/
  08-attributes/
  09-attribute-options/
  10-attribute-groups/
  11-attribute-families/
  12-products/
  13-configurable-products/
  14-variants/
  15-media-upload/

utils/api/                       # Reusable framework code
  config.js                      #   ─ Centralised URLs + SEED defaults
  auth-helper.js                 #   ─ OAuth2 token issue + cache
  request-wrapper.js             #   ─ Thin verb wrappers, response timing
  schema-validator.js            #   ─ ajv-backed JSON Schema validator
  response-validator.js          #   ─ Common assertion helpers
  index.js                       #   ─ Barrel re-export

payloads/                        # Per-resource payload generators
schemas/                         # JSON Schemas for response validation
fixtures/                        # `test`, `expect`, `api`, `unauthedApi`,
                                 #   `apiToken`, `uid`, test-data registry
```

## Prerequisites

1. UnoPim instance reachable at `BASE_URL` (default `http://127.0.0.1:8000`).
2. Admin credentials with access to the **Integrations** module.
3. `npm install` to pick up `@playwright/test`, `ajv`, `ajv-formats`.
4. (First run only) The suite will create an API integration through the
   admin UI and persist its `client_id` / `client_secret` to
   `.api-config.json`. Subsequent runs reuse the file. Set
   `API_CLIENT_ID` + `API_CLIENT_SECRET` to skip the bootstrap.

## Running

```sh
# All API tests
npm run test:api

# A single resource
npm run test:api:products

# A single spec file
npx playwright test tests/api/12-products/products.spec.js

# A single test by title regex
npx playwright test tests/api -g "12.3 - POST creates a simple product"

# Headed (for debugging the credential-bootstrap chromium step)
npx playwright test tests/api --headed --workers=1

# Trace mode
npx playwright test tests/api --trace=on
```

### CI-friendly invocation

```sh
# JUnit + HTML report, retries enabled
CI=1 npm run test:api:ci

# Parallelism (default workers = 5 — defined in playwright.config.js)
CI=1 npx playwright test tests/api --workers=4 --reporter=list,junit
```

## Environment variables

| Variable | Default | Purpose |
| --- | --- | --- |
| `API_BASE_URL` / `BASE_URL` | `http://127.0.0.1:8000` | Where UnoPim lives |
| `ADMIN_USERNAME` / `ADMIN_EMAIL` | `admin@example.com` | Used by global-setup + first-run bootstrap |
| `ADMIN_PASSWORD` | `admin123` | – |
| `API_CLIENT_ID` / `API_CLIENT_SECRET` | (read from `.api-config.json`) | Skip the UI bootstrap |
| `API_USERNAME` / `API_PASSWORD` | (admin creds) | Password-grant identity |
| `API_ROOT_CATEGORY` | `root` | Seed root for category tests |
| `API_DEFAULT_LOCALE` | `en_US` | Used by payload defaults |
| `API_SECONDARY_LOCALE` | `fr_FR` | Optional second locale |
| `API_DEFAULT_CHANNEL` | `default` | Used by payload defaults |
| `API_DEFAULT_CURRENCY` | `USD` | Used by price payloads |
| `API_DEFAULT_FAMILY` | `default` | Used by product payloads |
| `API_CONFIGURABLE_PATH` | `/configurable-products` | Endpoint for configurable POST |
| `API_SUPER_ATTRS` | `color,size` | Super-attribute codes (must exist in `default` family) |
| `API_VARIANT_COLOR_ATTR` | `color` | Color attribute code used by variant tests |
| `API_VARIANT_SIZE_ATTR` | `size` | Size attribute code used by variant tests |

See [`.env.example`](../../.env.example).

## Test independence & parallelism

Every test is **fully independent** — there is no `test.describe.serial`
anywhere in the suite. Each test:

- Builds its own data with the per-test `uid` fixture (Date.now + crypto random bytes).
- Captures every created code/sku in a per-file `Set` and reaps in `afterAll`.
- Reads shared parent resources (configurable parent, select attribute, seed
  category) from `beforeAll`, which Playwright runs **once per worker**. Those
  shared resources are minted with `uniqueCode()` so two workers running the
  same spec do not collide.

This means:

- You can shard the suite arbitrarily (`--shard=1/4`) — every test owns its data.
- You can filter to any single test by title (`-g "12.5"`) and it will pass.
- Failures are localised — one failing test never cascades.

`playwright.config.js` already sets `fullyParallel: true`. Default `workers: 5`.

## What every spec covers

Each resource folder ships tests across these categories:

| Category | What it asserts |
| --- | --- |
| **Positive** | Status 2xx, success envelope, body matches JSON Schema |
| **Negative** | Status ≥ 400 on malformed payloads |
| **Edge cases** | Empty body, oversize page numbers, invalid types |
| **Authorization** | 401 without bearer / invalid bearer / wrong scheme |
| **Validation** | 422 on missing required fields / duplicate codes |
| **Pagination** | `limit`/`page` honored, out-of-range pages return empty data |
| **Filter** | `filters={ field: [{ operator, value }] }` shape |
| **Sort** | `?sort=field&order=asc\|desc` returns ordered data |
| **Schema** | Body validated against `schemas/<resource>.schema.json` |
| **Response time** | Asserted against `RESPONSE_TIME` budgets in `utils/api/config.js` |
| **Headers** | `Content-Type: application/json` verified on read paths |

## Authoring new tests

1. Drop a new generator under `payloads/<resource>.payloads.js` (extend the
   barrel in `payloads/index.js`).
2. Add a Schema file under `schemas/<resource>.schema.json` (extend
   `schemas/index.js`).
3. Create `tests/api/<NN-resource>/<resource>.spec.js`:

   ```js
   const { test, expect } = require('../../../fixtures/api-fixtures');
   const { get, post } = require('../../../utils/api/request-wrapper');
   const { expectOk, expectListEnvelope, expectMatchesSchema } = require('../../../utils/api/response-validator');
   const schemas = require('../../../schemas');

   test.describe('My Resource', () => {
     test('1.1 - GET returns paginated envelope', async ({ api }) => {
       const result = await get(api, '/my-resource');
       expectOk(result);
       expectListEnvelope(result.body);
       for (const item of result.body.data) {
         expectMatchesSchema(schemas.myResource, item, 'myResource');
       }
     });
   });
   ```

4. Use `apiToken` if you need a raw, low-level `APIRequestContext` (e.g.
   custom headers or talking to OAuth endpoints outside `/api/v1/rest`).
   Use `unauthedApi` for 401-path negative tests.
5. Capture every code you create in a `Set` and reap in `afterAll` — see
   `categories.spec.js` for the pattern.

## Troubleshooting

- **"API credentials not configured" / suite-wide skip**: run once headed
  (`npx playwright test tests/api/01-oauth --headed --workers=1`) to allow
  the credential-bootstrap browser to complete. The generated credentials
  land in `.api-config.json`.
- **`ECONNREFUSED 127.0.0.1:8000`**: start UnoPim with `php artisan serve`
  (or set `API_BASE_URL` to point elsewhere).
- **Token expired during a long run**: tokens are worker-scoped (one per
  worker) and last ~1 h. Re-run a single failing spec to refresh.
- **JSON Schema warnings about missing ajv**: install dev deps —
  `npm install` after pulling. The built-in validator covers a permissive
  subset and won't fail the run if ajv is missing.

## Coverage matrix

| Endpoint Group | Spec | Tests |
| --- | --- | --- |
| OAuth2 | `01-oauth/oauth.spec.js` | 16 |
| Locales | `02-locales/locales.spec.js` | 12 |
| Currencies | `03-currencies/currencies.spec.js` | 11 |
| Channels | `04-channels/channels.spec.js` | 8 |
| Categories | `05-categories/categories.spec.js` | 23 |
| Category Fields | `06-category-fields/category-fields.spec.js` | 16 |
| Category Field Options | `07-category-field-options/category-field-options.spec.js` | 10 |
| Attributes | `08-attributes/attributes.spec.js` | 28 (incl. 8-type matrix) |
| Attribute Options | `09-attribute-options/attribute-options.spec.js` | 10 |
| Attribute Groups | `10-attribute-groups/attribute-groups.spec.js` | 12 |
| Attribute Families | `11-attribute-families/attribute-families.spec.js` | 14 |
| Products | `12-products/products.spec.js` | 24 |
| Configurable Products | `13-configurable-products/configurable-products.spec.js` | 10 |
| Variants | `14-variants/variants.spec.js` | 9 |
| Media Upload | `15-media-upload/media-upload.spec.js` | 8 |
| **Total** | | **211 tests** |

Last full-suite run: **211 / 211 passing** (0 failed, 0 skipped, ~100s with 2 workers).
