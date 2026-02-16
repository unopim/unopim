# Contract: Channel Adapter Interface

**Feature**: 001-channel-syndication
**Date**: 2026-02-14

---

## ChannelAdapterContract

Every channel adapter (Shopify, Salla, Easy Orders) MUST
implement this contract. It defines the unified interface
for all channel operations.

```
Webkul\ChannelConnector\Contracts\ChannelAdapterContract

Methods:
  testConnection(array $credentials): ConnectionResult
  syncProduct(Product $product, array $localeMappedData): SyncResult
  syncProducts(Collection $products, array $localeMappedData): BatchSyncResult
  fetchProduct(string $externalId, ?string $locale = null): ?array
  deleteProduct(string $externalId): bool
  getChannelFields(?string $locale = null): array
  getSupportedLocales(): array
  getSupportedCurrencies(): array
  isRtlLocale(string $localeCode): bool
  registerWebhooks(array $events, string $callbackUrl): bool
  verifyWebhook(Request $request): bool
  refreshCredentials(): ?array
  getRateLimitConfig(): RateLimitConfig
```

**Multi-Language Method Notes**:

- `syncProduct` / `syncProducts`: The `localeMappedData` parameter
  contains a locale-keyed structure:
  `["locales" => ["en" => [...], "ar" => [...]], "common" => [...]]`.
  Each adapter transforms this into channel-specific format.
- `fetchProduct`: Optional `locale` parameter fetches a specific
  locale's data from the channel (for conflict detection per-locale).
  When null, fetches all available locales.
- `getChannelFields`: Optional `locale` parameter returns field
  metadata in that locale (for display in mapping UI). Returns
  field list with `is_translatable` flag per field.
- `isRtlLocale`: Returns true if the given locale code uses an
  RTL script. Used by the sync engine to apply RTL transformations.

---

## ConnectionResult Value Object

```
Properties:
  success: bool
  message: string
  channelInfo: array (store name, product count, locales)
  errors: array
```

## SyncResult Value Object

```
Properties:
  success: bool
  externalId: ?string
  action: string (created / updated / deleted / skipped)
  errors: array
  dataHash: ?string
```

## BatchSyncResult Value Object

```
Properties:
  totalProcessed: int
  successCount: int
  failedCount: int
  skippedCount: int
  results: array of SyncResult
  errors: array
```

## RateLimitConfig Value Object

```
Properties:
  requestsPerSecond: ?int
  requestsPerMinute: ?int
  costPerQuery: ?int (for cost-based like Shopify GraphQL)
  costPerMutation: ?int
  maxCostPerSecond: ?int
```

---

## Channel-Specific Adapters

### ShopifyAdapter extends AbstractChannelAdapter

- API: GraphQL Admin API (version configurable)
- Auth: Access token in header
- Rate limit: Cost-based (50 points/sec standard)
- Bulk operations: Supported for large syncs
- Locales: Uses GraphQL `translationsRegister` mutation to push
  per-locale content; fetches translations via `translatableContent`
- RTL: Theme-dependent; adapter strips bidi markers when channel
  does not declare RTL support in its locale config
- Webhooks: HMAC-SHA256 verification

### SallaAdapter extends AbstractChannelAdapter

- API: REST v2 (`https://api.salla.dev/admin/v2`)
- Auth: OAuth2 with automatic token refresh
- Rate limit: Plan-dependent per minute
- Locales: Per-locale requests via `Accept-Language` header;
  iterates each mapped locale pair and sends separate API call
- RTL: Native Arabic RTL support — content sent without stripping
- Tax: Configurable VAT rate (default 15% for SAR)

### EasyOrdersAdapter extends AbstractChannelAdapter

- API: REST (endpoint TBD pending documentation)
- Auth: API key in header
- Rate limit: TBD
- Commission: Per-product commission rate and amount fields

---

## Event Dispatch Contract

All adapters MUST dispatch these events through the sync engine:

```
channel.connector.created.before / .after
channel.connector.updated.before / .after
channel.connector.deleted.before / .after
channel.sync.start.before / .after
channel.sync.product.before / .after
channel.sync.complete.before / .after
channel.sync.failed.before / .after
channel.conflict.detected.after
channel.conflict.resolved.after
channel.webhook.received.after
```

---

## ACL Permission Tree

```
channel_connector
├── connectors
│   ├── view
│   ├── create
│   ├── edit
│   └── delete
├── mappings
│   ├── view
│   └── edit
├── sync
│   ├── view
│   └── create
├── conflicts
│   ├── view
│   └── edit
└── webhooks
    ├── view
    └── manage
```
