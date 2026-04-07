# Coding Conventions — UnoPim

UnoPim follows **Laravel coding standards** enforced by [Laravel Pint](https://laravel.com/docs/pint) with the `laravel` preset.

---

## Code Style

### Formatter

```bash
# Check style
./vendor/bin/pint --test

# Fix style
./vendor/bin/pint

# Fix specific file
./vendor/bin/pint path/to/File.php
```

### Pint Configuration (`pint.json`)

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

### Key Style Rules

| Rule | Convention |
|---|---|
| Indentation | 4 spaces (no tabs) |
| Line endings | LF (`\n`) |
| Final newline | Required |
| Trailing whitespace | Trimmed |
| String quotes | Single quotes preferred (`'text'`) |
| Array syntax | Short arrays (`[]` not `array()`) |
| Visibility | Always declare (`public`, `protected`, `private`) |
| Type hints | Use PHP 8.1+ types where possible |
| Return types | Always declare return types |
| Null coalescing | Use `??` and `??=` over `isset()` ternary |
| Named arguments | Allowed and encouraged for readability |
| Enums | Use PHP 8.1 backed enums (see `Attribute/src/Enums/`) |

---

## Naming Conventions

| Entity | Convention | Example |
|---|---|---|
| Classes | PascalCase | `ProductController`, `AttributeRepository` |
| Methods | camelCase | `prepareQueryBuilder()`, `getFilteredProducts()` |
| Variables | camelCase | `$attributeFamily`, `$productType` |
| Constants | UPPER_SNAKE_CASE | `TEXT_TYPE`, `SELECT_FIELD_TYPE` |
| Config keys | snake_case with dots | `menu.admin`, `acl`, `core` |
| Route names | dot-separated, snake_case | `admin.catalog.products.index` |
| DB columns | snake_case | `attribute_family_id`, `is_required` |
| Event names | dot-separated | `catalog.product.create.after` |
| Translation keys | dot-separated | `admin::app.catalog.products.index.title` |
| Blade components | kebab-case with prefix | `<x-admin::form.control-group>` |
| Package namespaces | PascalCase | `Webkul\Product`, `Webkul\Admin` |

---

## DocBlocks

```php
/**
 * Create a new product.
 *
 * @param  array  $data
 * @return \Webkul\Product\Contracts\Product
 *
 * @throws \Webkul\Product\Exceptions\InvalidProductTypeException
 */
public function create(array $data): Product
{
    // ...
}
```

### Rules

- All public/protected methods need docblocks
- Keep descriptions concise (one line is ideal)
- Use `@param`, `@return`, `@throws` annotations
- Use fully qualified class names in `@param`/`@return`
- Private methods can skip docblocks if self-explanatory

---

## File Organization

```php
<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;      // Framework imports first
use Webkul\Core\Eloquent\TranslatableModel;  // Package imports second
use Webkul\Product\Contracts\Product as ProductContract; // Contracts last

class Product extends Model implements ProductContract
{
    // 1. Constants
    // 2. Properties ($fillable, $casts, etc.)
    // 3. Relationships
    // 4. Scopes
    // 5. Accessors/Mutators
    // 6. Business methods
}
```

---

## Error Handling

- Use Laravel exceptions (`abort()`, `ValidationException`)
- Custom exceptions in `Exceptions/` directories
- Validate input data before processing
- Use form request validation for HTTP endpoints
- Log errors with context: `Log::error('message', ['context' => $data])`

---

## Security Practices

- Always use parameterized queries (Eloquent handles this)
- Validate and sanitize all user input
- Use `$fillable` (whitelist) on models, never `$guarded = []` in production
- Use middleware for authentication and authorization
- ACL checks via `bouncer()->hasPermission()` for admin actions
- CSRF protection on all POST/PUT/DELETE routes
