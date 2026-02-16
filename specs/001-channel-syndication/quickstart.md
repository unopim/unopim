# Quickstart: Channel Syndication Feature

**Feature**: 001-channel-syndication
**Date**: 2026-02-14

---

## Prerequisites

- UnoPim installed and running (PHP 8.2+, MySQL 8.0+ or PostgreSQL 14+)
- At least one product with an attribute family created
- Queue worker running (`php artisan queue:work --queue=system,default`)
- Access to at least one channel's test/sandbox environment

## Step 1: Install Channel Connector Package

Register the ChannelConnector module in `config/concord.php`:

```
Webkul\ChannelConnector\Providers\ModuleServiceProvider
```

Run migrations:

```bash
php artisan migrate
```

Publish assets:

```bash
php artisan vendor:publish --tag=channel-connector-assets
npm run build
```

## Step 2: Install a Channel Adapter

For Shopify (already exists, will be refactored):
- Ensure `Webkul\Shopify\Providers\ModuleServiceProvider` is in concord.php

For Salla (new):
- Register `Webkul\Salla\Providers\ModuleServiceProvider` in concord.php
- Install Salla SDK: `composer require salla/laravel-starter-kit`

For Easy Orders (new):
- Register `Webkul\EasyOrders\Providers\ModuleServiceProvider` in concord.php

## Step 3: Configure a Channel Connector

1. Navigate to Admin → Integrations → Channel Connectors
2. Click "Create Connector"
3. Select channel type (Shopify / Salla / Easy Orders)
4. Enter credentials:
   - **Shopify**: Shop URL + Access Token
   - **Salla**: Click "Authorize with Salla" (OAuth2 flow)
   - **Easy Orders**: API Key
5. Click "Test Connection"
6. If successful, click "Save"

## Step 4: Configure Locale Mapping

1. Open the connector → "Settings" tab → "Locale Mapping"
2. The system displays all active UnoPim locales alongside
   the channel's supported languages (fetched via API)
3. Map each UnoPim locale to the corresponding channel locale:
   - Example Shopify: `en_US → en`, `fr_FR → fr`, `ar_AE → ar`
   - Example Salla: `ar_AE → ar` (primary), `en_US → en`
4. Unmapped locales will be skipped during sync
5. For RTL locales (ar_AE, he_IL): if the channel supports
   RTL natively (Salla), content is sent as-is; otherwise
   a warning badge appears indicating bidi markers will be stripped
6. Click "Save Locale Mapping"

## Step 5: Configure Field Mapping

1. Open the connector → "Field Mapping" tab
2. Review auto-suggested mappings (sku→sku, name→title, etc.)
3. For locale-specific attributes (name, description), the
   mapping UI shows a "Translatable" badge — these values
   will be synced per-locale based on Step 4's locale mapping
4. Adjust or add mappings as needed
5. For each mapping, set the sync direction: Export, Import, or Both
6. Click "Save Mappings"

## Step 6: Sync Products

1. Go to connector → "Sync" tab
2. Select sync type:
   - **Incremental**: Only changed products (recommended for regular use)
   - **Full**: All products (first-time sync)
   - **Single**: From the product edit page
3. Optionally filter locales to sync (default: all mapped locales)
4. Click "Start Sync"
5. Monitor progress on the sync dashboard — per-locale progress
   is shown for multi-locale syncs

## Step 7: Monitor & Manage

- **Sync Dashboard**: Admin → Integrations → Sync Monitor
- **View failures**: Click on a failed job → see per-product errors
- **Retry failed**: Click "Retry Failed Products"
- **Resolve conflicts**: Admin → Integrations → Conflicts

## Verification Checklist

- [ ] Connector shows "connected" status
- [ ] Locale mapping displays channel's supported languages
- [ ] At least one UnoPim locale is mapped to a channel locale
- [ ] Field mappings display correctly with "Translatable" badges
- [ ] Test sync of 1 product succeeds
- [ ] Product appears in external channel with correct language
- [ ] Multi-locale content syncs correctly (verify each mapped locale)
- [ ] Arabic/RTL content appears correctly in RTL-capable channels
- [ ] Sync dashboard shows job progress
- [ ] Failed products show clear, localized error messages
- [ ] Retry of failed products works
- [ ] Conflict diff shows per-locale values for translatable fields

## Common Issues

**Connection test fails**: Verify credentials and ensure the
channel's API is accessible from your server.

**Products not syncing**: Ensure products are assigned to a
channel and have values for all required mapped fields.

**Rate limit errors**: The system automatically throttles API
calls. For large catalogs, use incremental sync to spread
load over time.

**Conflict detected**: Check the Conflicts page to review and
resolve. Set a default conflict strategy in connector settings
to auto-resolve future conflicts.
