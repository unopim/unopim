# UnoPim Adapter Replication Scripts

## Quick Start: Replicate an Adapter

Use the replication script to quickly create a new adapter from the Salla template:

```bash
# Syntax
./scripts/replicate-adapter.sh <AdapterName> <adapter_name> "<Adapter Title>"

# Examples
./scripts/replicate-adapter.sh Amazon amazon "Amazon SP-API"
./scripts/replicate-adapter.sh WooCommerce woocommerce "WooCommerce"
./scripts/replicate-adapter.sh Ebay ebay "eBay"
./scripts/replicate-adapter.sh Noon noon "Noon"
./scripts/replicate-adapter.sh Magento2 magento2 "Magento 2"
./scripts/replicate-adapter.sh EasyOrders easyorders "EasyOrders"
```

## What the Script Does

1. ✅ Copies complete Salla adapter structure (30 files)
2. ✅ Performs global find-replace (Salla → YourAdapter)
3. ✅ Updates translations with your adapter title
4. ✅ Creates placeholder API base URLs
5. ✅ Cleans up Salla-specific OAuth references

## After Running the Script

### 1. Customize Credential Fields
Edit `packages/Webkul/{Adapter}/src/Models/{Adapter}CredentialsConfig.php`:

**Amazon Example:**
```php
protected $fillable = [
    'seller_id',
    'marketplace_id',
    'region',
    'sp_api_refresh_token',
    'lwa_access_token',
    'lwa_refresh_token',
    'expires_at',
    'active',
    // ... rest
];
```

**WooCommerce Example:**
```php
protected $fillable = [
    'store_url',
    'consumer_key',
    'consumer_secret',
    'version',
    'active',
    // ... rest
];
```

See `docs/adapter-implementation-template.md` for all platform-specific fields.

### 2. Update Migrations
Edit `packages/Webkul/{Adapter}/src/Database/Migration/*_credentials_config.php` to match your credential fields.

### 3. Implement API Logic
Edit `packages/Webkul/{Adapter}/src/Adapters/{Adapter}Adapter.php`:

```php
protected const API_BASE = 'https://api.your-platform.com/v1';

public function testConnection(array $credentials): ConnectionResult
{
    // Implement your platform's connection test
}

public function syncProduct(Product $product, array $localeMappedData): SyncResult
{
    // Implement your platform's product sync
}

// ... implement other abstract methods
```

### 4. Run Migrations
```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

### 5. Test in Browser
Navigate to: `/admin/{adapter_name}/credentials`

## Platform-Specific Guides

### Amazon SP-API
- **API Docs:** https://developer-docs.amazon.com/sp-api/
- **Auth:** LWA OAuth2 (Login with Amazon)
- **Key Features:** Multi-region, marketplace selection
- **Rate Limits:** Dynamic per endpoint

### eBay
- **API Docs:** https://developer.ebay.com/api-docs/static/rest-home.html
- **Auth:** OAuth 2.0
- **Key Features:** Site ID selection, category mapping
- **Rate Limits:** Per endpoint

### Noon
- **API Docs:** https://developer.noon.partners/
- **Auth:** Dual-header (x-partner-id + Authorization)
- **Key Features:** Multi-marketplace (UAE, KSA, EGY)
- **Rate Limits:** TBD

### WooCommerce
- **API Docs:** https://woocommerce.github.io/woocommerce-rest-api-docs/
- **Auth:** Consumer key/secret (Basic Auth)
- **Key Features:** Self-hosted, webhooks
- **Rate Limits:** None (self-hosted)

### Magento 2
- **API Docs:** https://developer.adobe.com/commerce/webapi/rest/
- **Auth:** Admin token (Bearer)
- **Key Features:** Store view mapping
- **Rate Limits:** Configurable

### EasyOrders
- **API Docs:** [Platform specific]
- **Auth:** API key
- **Key Features:** Commission tracking
- **Rate Limits:** TBD

## Time Estimates

| Adapter | Time (with script) |
|---------|-------------------|
| Amazon | 6 hours |
| WooCommerce | 4 hours |
| eBay | 5 hours |
| Magento2 | 5 hours |
| Noon | 4 hours |
| EasyOrders | 4 hours |
| **Total** | **28 hours** |

Compare to manual implementation: **60+ hours**

## Troubleshooting

### Script fails with "permission denied"
```bash
chmod +x scripts/replicate-adapter.sh
```

### Find-replace didn't work on macOS
The script uses `sed -i ''` for macOS. On Linux, change to `sed -i`.

### Routes not registered
```bash
php artisan route:list | grep {adapter_name}
php artisan config:clear
```

### Migrations fail
Check table names in migrations match your naming convention.

## Support

- Full Template Guide: `docs/adapter-implementation-template.md`
- Reference Implementation: `packages/Webkul/Salla/`
- Status Report: `docs/EPIC-001-implementation-status.md`
