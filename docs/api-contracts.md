# UnoPim - API Contracts

## Overview

UnoPim provides a RESTful API (v1) for machine-to-machine integration. The API uses OAuth2 authentication (Laravel Passport) with granular permission control per API key.

- **Base URL:** `/v1/rest/`
- **Authentication:** OAuth2 Bearer Token (Password Grant)
- **Content Type:** `application/json`
- **Middleware:** `auth:api`, `api.scope`, `accept.json`, `request.locale`

---

## Authentication

### Obtaining an Access Token

```
POST /oauth/token
Content-Type: application/json

{
  "grant_type": "password",
  "client_id": "<oauth_client_id>",
  "client_secret": "<oauth_client_secret>",
  "username": "<admin_email>",
  "password": "<admin_password>"
}
```

**Response:**
```json
{
  "token_type": "Bearer",
  "expires_in": 3600,
  "access_token": "<jwt_token>",
  "refresh_token": "<refresh_token>"
}
```

### Using the Token
```
Authorization: Bearer <access_token>
```

### API Key Management (Admin UI)
- **List:** GET `/{admin_url}/integrations/api-keys`
- **Create:** POST `/{admin_url}/integrations/api-keys/create`
- **Edit:** PUT `/{admin_url}/integrations/api-keys/edit/{id}`
- **Delete:** DELETE `/{admin_url}/integrations/api-keys/edit/{id}`
- **Generate Key:** POST `/{admin_url}/integrations/api-keys/generate`
- **Regenerate Secret:** POST `/{admin_url}/integrations/api-keys/re-generate-secrete`

---

## Catalog API Endpoints

### Attributes

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/attributes` | admin.api.attributes.index | List all attributes (paginated) |
| GET | `/v1/rest/attributes/{code}` | admin.api.attributes.get | Get attribute by code |
| POST | `/v1/rest/attributes` | admin.api.attributes.store | Create new attribute |
| PUT | `/v1/rest/attributes/{code}` | admin.api.attributes.update | Update attribute |

### Attribute Options

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/attributes/{code}/options` | admin.api.attribute_options.get | List attribute options |
| POST | `/v1/rest/attributes/{code}/options` | admin.api.attribute_options.store_option | Create option |
| PUT | `/v1/rest/attributes/{code}/options` | admin.api.attribute_options.update_option | Update option |

### Attribute Groups

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/attribute-groups` | admin.api.attribute_groups.index | List attribute groups |
| GET | `/v1/rest/attribute-groups/{code}` | admin.api.attribute_groups.get | Get group by code |
| POST | `/v1/rest/attribute-groups` | admin.api.attribute_groups.store | Create group |
| PUT | `/v1/rest/attribute-groups/{code}` | admin.api.attribute_groups.update | Update group |

### Attribute Families

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/families` | admin.api.families.index | List families |
| GET | `/v1/rest/families/{code}` | admin.api.families.get | Get family by code |
| POST | `/v1/rest/families` | admin.api.families.store | Create family |
| PUT | `/v1/rest/families/{code}` | admin.api.families.update | Update family |

### Category Fields

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/category-fields` | admin.api.category-fields.index | List category fields |
| GET | `/v1/rest/category-fields/{code}` | admin.api.category-fields.get | Get field by code |
| POST | `/v1/rest/category-fields` | admin.api.category-fields.store | Create field |
| PUT | `/v1/rest/category-fields/{code}` | admin.api.category-fields.update | Update field |
| GET | `/v1/rest/category-fields/{code}/options` | admin.api.category-fields_options.get | List field options |
| POST | `/v1/rest/category-fields/{code}/options` | admin.api.category-fields-options.store_option | Create option |
| PUT | `/v1/rest/category-fields/{code}/options` | admin.api.category-fields-options.update_option | Update option |

### Categories

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/categories` | admin.api.categories.index | List categories |
| GET | `/v1/rest/categories/{code}` | admin.api.categories.get | Get category by code |
| POST | `/v1/rest/categories` | admin.api.categories.store | Create category |
| PUT | `/v1/rest/categories/{code}` | admin.api.categories.update | Full update |
| PATCH | `/v1/rest/categories/{code}` | admin.api.categories.patch | Partial update |
| DELETE | `/v1/rest/categories/{code}` | admin.api.categories.delete | Delete category |

### Simple Products

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/products` | admin.api.products.index | List products |
| GET | `/v1/rest/products/{code}` | admin.api.products.get | Get product by SKU |
| POST | `/v1/rest/products` | admin.api.products.store | Create product |
| PUT | `/v1/rest/products/{code}` | admin.api.products.update | Full update |
| PATCH | `/v1/rest/products/{sku}` | admin.api.products.patch | Partial update |
| DELETE | `/v1/rest/products/{code}` | admin.api.products.delete | Delete product |

### Configurable Products

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/configrable-products` | admin.api.configrable_products.index | List configurable products |
| GET | `/v1/rest/configrable-products/{code}` | admin.api.configrable_products.get | Get by code |
| POST | `/v1/rest/configrable-products` | admin.api.configrable_products.store | Create |
| PUT | `/v1/rest/configrable-products/{code}` | admin.api.configrable_products.update | Full update |
| PATCH | `/v1/rest/configrable-products/{code}` | admin.api.configrable_products.patch | Partial update |

### Media Files

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| POST | `/v1/rest/media-files/category` | admin.api.media-files.category.store | Upload category media |
| POST | `/v1/rest/media-files/product` | admin.api.media-files.product.store | Upload product media |
| POST | `/v1/rest/media-files/swatch` | admin.api.media-files.attribute.options.store | Upload swatch media |

---

## Settings API Endpoints

### Locales

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/locales` | admin.api.locales.index | List all locales |
| GET | `/v1/rest/locales/{code}` | admin.api.locales.get | Get locale by code |

### Channels

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/channels` | admin.api.channels.index | List all channels |
| GET | `/v1/rest/channels/{code}` | admin.api.channels.get | Get channel by code |

### Currencies

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/v1/rest/currencies` | admin.api.currencies.index | List all currencies |
| GET | `/v1/rest/currencies/{code}` | admin.api.currencies.get | Get currency by code |

---

## Admin Web Routes (Non-API)

### Authentication (Public)
| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/{admin_url}/login` | admin.session.create | Login page |
| POST | `/{admin_url}/login` | admin.session.store | Authenticate |
| DELETE | `/{admin_url}/logout` | admin.session.destroy | Logout |
| GET | `/{admin_url}/forget-password` | admin.forget_password.create | Forgot password |
| POST | `/{admin_url}/forget-password` | admin.forget_password.store | Send reset email |
| GET | `/{admin_url}/reset-password/{token}` | admin.reset_password.create | Reset form |
| POST | `/{admin_url}/reset-password` | admin.reset_password.store | Save new password |

### Dashboard
| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/{admin_url}/dashboard` | admin.dashboard.index | Dashboard page |
| GET | `/{admin_url}/dashboard/stats` | admin.dashboard.stats | Dashboard statistics |

### Catalog - Products (42 routes)
Full CRUD for products, attributes, attribute groups, attribute families, categories, category fields. Includes mass operations, bulk edit, search, and variant management.

### Settings (60+ routes)
Full CRUD for channels, currencies, locales, roles, users. Complete data transfer management (imports, exports, tracker) with validation, execution, and download capabilities.

### Configuration
| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/{admin_url}/configuration/{slug?}/{slug2?}` | admin.configuration.edit | Config editor |
| POST | `/{admin_url}/configuration/{slug?}/{slug2?}` | admin.configuration.store | Save config |

### Magic AI (18 routes)
Content generation, image generation, translation services, custom prompts, system prompts.

### History & Version Control (5 routes)
View history, compare versions, restore versions, delete versions.

### Webhook Management (6 routes)
Webhook settings CRUD, webhook log viewing and management.

### Completeness (4 routes)
Completeness settings per family, dashboard data.

### Notifications (4 routes)
List, fetch, mark as read, bulk read.

---

## API Middleware Stack

1. **`auth:api`** - OAuth2 token validation via Laravel Passport
2. **`api.scope`** - ACL permission validation (ScopeMiddleware)
3. **`accept.json`** - Enforces JSON response format
4. **`request.locale`** - Sets locale from request headers

---

## API Permission Scopes

API keys have their own ACL system (`api-acl.php`) mirroring the admin ACL:

- `api.catalog.attributes` (index, get, create, update)
- `api.catalog.attribute_groups` (index, get, create, update)
- `api.catalog.families` (index, get, create, update)
- `api.catalog.category_fields` (index, get, create, update)
- `api.catalog.categories` (index, get, create, update, delete)
- `api.catalog.products` (index, get, create, update, delete)
- `api.settings.locales` (index, get)
- `api.settings.channels` (index, get)
- `api.settings.currencies` (index, get)

---

## External API Documentation

Official Postman collection: [UnoPim APIs Documentation](https://documenter.getpostman.com/view/37137259/2sBXVhEWjS)
