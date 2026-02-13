# UnoPim DATA/EXTERNAL Layer Skill

Use this skill when working with database queries, Eloquent models, repositories, external services (Elasticsearch, OpenAI/MagicAI, Queue, Cache), or the product values JSON structure.

## Database Grammar Pattern

Use `GrammarQueryManager` for cross-DB compatible queries:

```php
use Webkul\Core\Helpers\Database\GrammarQueryManager;

$grammar = GrammarQueryManager::getGrammar(); // Auto-detects driver
$grammar->jsonExtract('values', 'common', 'sku');  // DB-specific JSON extraction
$grammar->groupConcat('column', 'alias', 'order_col', false, ', ');
$grammar->getBooleanValue(true);  // MySQL: 1, PostgreSQL: 't'
```

**Contract:** `Webkul\Core\Contracts\Database\Grammar`
**Grammars:** `Grammars/MySQLGrammar`, `Grammars/PostgresGrammar`, `Grammars/SQLiteGrammar`
**Location:** `packages/Webkul/Core/src/Helpers/Database/`

## Eloquent Model Conventions

Every model follows this pattern:

```php
namespace Webkul\{Package}\Models;

use Webkul\{Package}\Contracts\{ModelName} as {ModelName}Contract;

class ModelName extends Model implements ModelNameContract, HistoryAuditable
{
    use HasFactory, HistoryTrait;

    protected $fillable = [...];
    protected $casts = ['json_col' => 'array'];
}
```

**Key traits:**
- `HistoryTrait` + `HistoryAuditable` = version control (set `$historyTags`, `$historyFields`)
- `NodeTrait` = nested set for hierarchies (Category)
- `Visitable` = page visit tracking
- `HasFactory` = test factories

**Translatable models** extend `TranslatableModel` instead of `Model`:
```php
class Channel extends TranslatableModel implements ChannelContract
{
    public $translatedAttributes = ['name'];
    // Creates {table}_translations table automatically
}
```

## Product Values JSON Structure

Products store attribute values in a JSON `values` column:

```php
// AbstractType constants (Webkul\Product\Type\AbstractType)
PRODUCT_VALUES_KEY       = 'values'
COMMON_VALUES_KEY        = 'common'
LOCALE_VALUES_KEY        = 'locale_specific'
CHANNEL_VALUES_KEY       = 'channel_specific'
CHANNEL_LOCALE_VALUES_KEY = 'channel_locale_specific'
CATEGORY_VALUES_KEY      = 'categories'
ASSOCIATION_VALUES_KEY   = 'associations'
```

```json
{
  "common": { "sku": "PROD-001", "status": true },
  "locale_specific": { "en_US": { "name": "Product Name" } },
  "channel_specific": { "default": { "price": 29.99 } },
  "channel_locale_specific": { "default": { "en_US": { "meta_title": "..." } } },
  "categories": [1, 2, 3],
  "associations": { "related_products": [4], "up_sells": [6], "cross_sells": [8] }
}
```

**Value scope resolution** (Attribute model methods):
- Channel + Locale: `channel_locale_specific->{channel}->{locale}->{code}`
- Channel only: `channel_specific->{channel}->{code}`
- Locale only: `locale_specific->{locale}->{code}`
- Common: `common->{code}`

Use `$attribute->getValueFromProductValues($values, $channel, $locale)` to read.
Use `$attribute->setProductValue($value, $productValues, $channel, $locale)` to write.

## Repository Pattern

Base: `Webkul\Core\Eloquent\Repository` (extends Prettus, implements `CacheableInterface`)

```php
namespace Webkul\{Package}\Repositories;

use Webkul\Core\Eloquent\Repository;

class MyRepository extends Repository
{
    public function model() { return \Webkul\{Package}\Contracts\MyModel::class; }
}
```

**Key repositories:**
- `ProductRepository` - delegates create/update to type instance (`$product->getTypeInstance()->create()`)
- `CategoryRepository` - constants: `ADDITIONAL_VALUES_KEY`, `LOCALE_VALUES_KEY`, `COMMON_VALUES_KEY`
- `AttributeRepository` - handles options for select/multiselect/checkbox types

**Binding:** Repositories are bound via Concord module registration, resolved via contract interfaces.

## External Services

### Elasticsearch
- Config: `config/elasticsearch.php`
- Classes: `ElasticSearchQuery`, `ProductNormalizer`, `Product Observer`
- Facade: `elasticsearch` (singleton via CoreServiceProvider)

### MagicAI / LLM
- Interface: `Webkul\MagicAI\Contracts\LLMModelInterface`
- Implementations: `OpenAIService`, `GroqService`, `GeminiService`, `OllamaService`
- Builder: `MagicAI` class with fluent API

### Queue
- Driver: Redis (default), with 3 separate databases (default, cache, session)
- Jobs: `packages/Webkul/DataTransfer/src/Jobs/` for import/export

### Cache
- Repository caching via `CacheableRepository` trait
- FPC invalidation via event listeners

### Filesystem
- `FileStorer` service for file uploads (products, categories)
- Product images stored at `product/{id}/`

## Key Model Reference

| Model | Extends | Key Traits | JSON Cols |
|-------|---------|------------|-----------|
| Product | Model | HasFactory, Visitable, HistoryTrait | values, additional |
| Category | Model | HasFactory, NodeTrait, Visitable, HistoryTrait | additional_data |
| Channel | TranslatableModel | HasFactory, HistoryTrait | - |
| Attribute | TranslatableModel | HasFactory, HistoryTrait | - |
| Locale | Model | Auditable, HasFactory | - |
| Currency | Model | Auditable, HasFactory | - |
| Admin | Authenticatable | Auditable, HasApiTokens, HasFactory, Notifiable | - |
| Role | Model | HasFactory, HistoryTrait | permissions |

## Key Rules

- ALWAYS use `GrammarQueryManager::getGrammar()` for raw SQL - never write MySQL-specific queries
- ALWAYS implement the Contract interface for models (`Webkul\{Package}\Contracts\{Model}`)
- NEVER access product values directly - use Attribute model methods or AbstractType constants
- JSON columns MUST be cast as `'array'` in model `$casts`
- PostgreSQL sequences need `DatabaseSequenceHelper::fixSequences()` after bulk inserts
- Translatable models require `$translatedAttributes` array and a `{table}_translations` migration
- SKU attribute (`code='sku'`) is protected and cannot be deleted
