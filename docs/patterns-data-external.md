# UnoPim - DATA / EXTERNAL Layer Patterns & Skills

> Reference documentation for the Data and External Services architectural layer.
> Generated from exhaustive codebase scan - 2026-02-08

---

## 1. DATABASE PATTERNS

### 1.1 Grammar Query Manager Pattern

**File**: `packages/Webkul/Core/src/Helpers/Database/GrammarQueryManager.php`

**Class**: `Webkul\Core\Helpers\Database\GrammarQueryManager`

**Purpose**: Singleton pattern to manage database-specific SQL grammar implementations across MySQL, PostgreSQL, and SQLite.

**Implementation**:
```php
class GrammarQueryManager
{
    protected static array $instances = [];

    public static function getGrammar(?string $driver = null): Grammar
    {
        $driver = $driver ?? DB::getDriverName();

        if (isset(static::$instances[$driver])) {
            return static::$instances[$driver];
        }

        static::$instances[$driver] = match ($driver) {
            'pgsql' => new PostgresGrammar,
            'mysql' => new MySQLGrammar,
            'sqlite' => new SQLiteGrammar,
            default => throw new \RuntimeException("Unsupported DB driver: {$driver}")
        };

        return static::$instances[$driver];
    }
}
```

**Usage**: Provides driver-specific SQL functions like `groupConcat()`, `jsonExtract()`, `orderByField()` etc.

### 1.2 Database Grammar Implementations

**Contract**: `Webkul\Core\Contracts\Database\Grammar`

**Methods**:
- `groupConcat(string $column, ?string $alias = null, ?string $orderBy = null, bool $distinct = false, string $separator = ', '): string`
- `concat(string ...$parts): string`
- `coalesce(array $columns, ?string $alias = null): string`
- `length(string $column): string`
- `jsonExtract(string $column, string ...$pathSegments): string`
- `orderByField(string $column, array $ids, string $type = ''): string`
- `getRegexOperator(): string`
- `getBooleanValue(mixed $value): mixed`

**MySQL Grammar**: `Webkul\Core\Helpers\Database\Grammars\MySQLGrammar`
- Uses `GROUP_CONCAT()` for aggregation
- Uses `JSON_EXTRACT()` + `JSON_UNQUOTE()` for JSON paths
- Uses `FIELD()` for custom ordering
- Regex operator: `REGEXP`
- Boolean values: `1` (true), `0` (false)

**PostgreSQL Grammar**: `Webkul\Core\Helpers\Database\Grammars\PostgresGrammar`
- Uses `STRING_AGG()` for aggregation
- Uses `->` and `->>` operators for JSON paths
- Uses `array_position()` for custom ordering
- Regex operator: `~`
- Boolean values: `'t'` (true), `'f'` (false)

**SQLite Grammar**: `Webkul\Core\Helpers\Database\Grammars\SQLiteGrammar`
- Uses `GROUP_CONCAT()` for aggregation
- Uses `json_extract()` for JSON paths
- Uses `CASE WHEN` for custom ordering
- Regex operator: `REGEXP`
- Boolean values: `1` (true), `0` (false)

### 1.3 Database Sequence Helper

**File**: `packages/Webkul/Core/src/Helpers/Database/DatabaseSequenceHelper.php`

**Class**: `Webkul\Core\Helpers\Database\DatabaseSequenceHelper`

**Methods**:
```php
public static function fixSequences(array $tables): void
// Fixes PostgreSQL sequences for multiple tables

public static function fixSequence(string $table, ?string $tablePrefix = null)
// Fixes single PostgreSQL sequence
```

**Purpose**: Handles PostgreSQL sequence resets after bulk operations, essential for auto-increment fixes.

### 1.4 Database Configuration

**File**: `config/database.php`

**Supported Drivers**:
- **MySQL**: InnoDB with `ROW_FORMAT=DYNAMIC`, `utf8mb4` charset
- **PostgreSQL**: Public schema, `sslmode: prefer`
- **SQLite**: File-based database
- **SQL Server**: SQLSRV driver support

**Redis Configuration**: Supports 3 separate databases for default, cache, and session.

---

## 2. ELOQUENT MODEL PATTERNS

### 2.1 Product Model

**File**: `packages/Webkul/Product/src/Models/Product.php`

**Class**: `Webkul\Product\Models\Product extends Model`

**Implements**:
- `HistoryAuditable`
- `PresentableHistoryInterface`
- `Webkul\Product\Contracts\Product`

**Traits**:
- `HasFactory`
- `Visitable` (from shetabit/visitor)
- `HistoryTrait`

**Fillable**:
```php
protected $fillable = [
    'type',
    'attribute_family_id',
    'sku',
    'parent_id',
    'status',
];
```

**Casts**:
```php
protected $casts = [
    'additional' => 'array',
    'values'     => 'array',
];
```

**Relationships**:
```php
public function parent(): BelongsTo // Self-referential
public function attribute_family(): BelongsTo
public function super_attributes(): BelongsToMany // Through product_super_attributes
public function images(): HasMany // Ordered by position
public function variants(): HasMany // Self-referential (products with parent_id)
public function completenessScores(): HasMany
```

**Database Table**: `products`
- Primary Key: `id` (auto-increment)
- Unique: `sku`
- Indexes: `sku`, `type`, `attribute_family_id`, `parent_id`, composite `[attribute_family_id, parent_id]`
- Foreign Keys: `attribute_family_id` (restrict), `parent_id` (cascade)

**JSON Column Structure** (`values`):
```php
[
    'common' => [
        'sku' => 'PRODUCT-SKU',
        'attribute_code' => 'value',
    ],
    'locale_specific' => [
        'en' => ['attribute_code' => 'value'],
        'fr' => ['attribute_code' => 'value'],
    ],
    'channel_specific' => [
        'default' => ['attribute_code' => 'value'],
    ],
    'channel_locale_specific' => [
        'default' => [
            'en' => ['attribute_code' => 'value'],
        ],
    ],
    'categories' => [1, 2, 3],
    'associations' => [
        'related_products' => [4, 5],
        'up_sells' => [6, 7],
        'cross_sells' => [8, 9],
    ],
]
```

**Custom Builder**: Uses `Webkul\Product\Database\Eloquent\Builder`

**Presenters**:
- `values` -> `ProductValuesPresenter`
- `status` -> `BooleanPresenter`

### 2.2 Category Model

**File**: `packages/Webkul/Category/src/Models/Category.php`

**Class**: `Webkul\Category\Models\Category extends Model`

**Implements**:
- `CategoryContract`
- `HistoryAuditable`
- `PresentableHistoryInterface`

**Traits**:
- `HasFactory`
- `NodeTrait` (from kalnoy/nestedset - Nested Set Pattern)
- `Visitable`
- `HistoryTrait`

**Fillable**:
```php
protected $fillable = [
    'code',
    'parent_id',
];
```

**Casts**:
```php
protected $casts = [
    'additional_data' => 'array',
];
```

**Appends**:
```php
protected $appends = ['name'];
```

**Nested Set Columns**: `_lft`, `_rgt` (managed by NodeTrait)

**Relationships**:
```php
public function parent_category(): BelongsTo
```

**Accessor** (Computed Property):
```php
protected function name(): Attribute
{
    return Attribute::make(
        get: fn (?string $value, array $attributes) =>
            $this->additional_data['locale_specific'][core()->getRequestedLocaleCode()]['name'] ?? '['.$this->code.']'
    )->shouldCache();
}
```

**Custom Builder**: Uses `Webkul\Category\Database\Eloquent\Builder`

**Presenters**:
- `additional_data` -> `JsonDataPresenter`

### 2.3 Locale Model

**File**: `packages/Webkul/Core/src/Models/Locale.php`

**Class**: `Webkul\Core\Models\Locale extends Model`

**Implements**:
- `AuditableContract` (from owenIt/auditing)
- `LocaleContract`

**Traits**:
- `Auditable`
- `HasFactory`

**Fillable**:
```php
protected $fillable = [
    'code',
    'status',
];
```

**Appends**:
```php
protected $appends = ['name'];
```

**Relationships**:
```php
public function user(): HasMany // Many admins can use this locale as UI locale
public function channel(): BelongsToMany // Through channel_locales
```

**Accessor** (Computed Property):
```php
protected function name(): Attribute
{
    return Attribute::make(
        get: fn (?string $value, array $attributes) =>
            \Locale::getDisplayName($attributes['code'], app()->getLocale())
    )->shouldCache();
}
```

**Database Table**: `locales`
- Unique: `code`
- Junction Table: `channel_locales` with composite primary key `[channel_id, locale_id]`

### 2.4 Channel Model

**File**: `packages/Webkul/Core/src/Models/Channel.php`

**Class**: `Webkul\Core\Models\Channel extends TranslatableModel`

**Implements**:
- `ChannelContract`
- `HistoryAuditable`

**Traits**:
- `HasFactory`
- `HistoryTrait`

**Translatable Attributes**:
```php
public $translatedAttributes = [
    'name',
];
```

**Fillable**:
```php
protected $fillable = [
    'code',
    'name',
    'root_category_id',
];
```

**History Configuration**:
```php
protected $historyTags = ['channel'];
protected $historyFields = [
    'root_category_id',
];
protected $historyProxyFields = [
    'currencies',
    'locales',
];
```

**Relationships**:
```php
public function locales(): BelongsToMany // Through channel_locales
public function currencies(): BelongsToMany // Through channel_currencies
public function root_category(): BelongsTo
```

**Junction Tables**:
- `channel_locales`: Composite primary key `[channel_id, locale_id]`
- `channel_currencies`: Composite primary key `[channel_id, currency_id]`

### 2.5 Currency Model

**File**: `packages/Webkul/Core/src/Models/Currency.php`

**Class**: `Webkul\Core\Models\Currency extends Model`

**Implements**:
- `AuditableContract`
- `CurrencyContract`

**Traits**:
- `Auditable`
- `HasFactory`

**Fillable**:
```php
protected $fillable = [
    'code',
    'symbol',
    'decimal',
    'status',
];
```

**Appends**:
```php
protected $appends = ['name'];
```

**Mutator**:
```php
public function setCodeAttribute($code): void
{
    $this->attributes['code'] = strtoupper($code);
}
```

**Relationships**:
```php
public function exchange_rate(): HasOne
public function channel(): BelongsToMany // Through channel_currencies
```

**Accessor** (Computed Property):
```php
protected function name(): Attribute
{
    return Attribute::make(
        get: function (?string $value, array $attributes) {
            try {
                return Currencies::getName($attributes['code'],
                    \Locale::getPrimaryLanguage(app()->getLocale()));
            } catch (\Exception $e) {
                return $attributes['code'];
            }
        }
    )->shouldCache();
}
```

### 2.6 Attribute Model

**File**: `packages/Webkul/Attribute/src/Models/Attribute.php`

**Class**: `Webkul\Attribute\Models\Attribute extends TranslatableModel`

**Implements**:
- `AttributeContract`
- `HistoryAuditable`

**Traits**:
- `HasFactory`
- `HistoryTrait`

**Translatable Attributes**:
```php
public $translatedAttributes = ['name'];
```

**Fillable**:
```php
protected $fillable = [
    'code',
    'type',
    'enable_wysiwyg',
    'position',
    'swatch_type',
    'is_required',
    'is_unique',
    'validation',
    'regex_pattern',
    'value_per_locale',
    'value_per_channel',
    'is_filterable',
    'ai_translate',
];
```

**Type Constants**:
```php
const TEXT_TYPE = 'text';
const TEXTAREA_TYPE = 'textarea';
const BOOLEAN_FIELD_TYPE = 'boolean';
const PRICE_FIELD_TYPE = 'price';
const SELECT_FIELD_TYPE = 'select';
const MULTISELECT_FIELD_TYPE = 'multiselect';
const DATETIME_FIELD_TYPE = 'datetime';
const DATE_FIELD_TYPE = 'date';
const CHECKBOX_FIELD_TYPE = 'checkbox';
const FILE_ATTRIBUTE_TYPE = 'file';
const IMAGE_ATTRIBUTE_TYPE = 'image';
const GALLERY_ATTRIBUTE_TYPE = 'gallery';
```

**Relationships**:
```php
public function options(): HasMany
```

**Key Methods**:
```php
// Determine where value is stored in product values JSON
public function getValueFromProductValues(
    array $values,
    string $currentChannelCode,
    string $currentLocaleCode
): mixed

// Get flat attribute name for form input
public function getFlatAttributeName(string $currentChannelCode, string $currentLocaleCode): string

// Get formatted input field name for forms
public function getAttributeInputFieldName(string $currentChannelCode, string $currentLocaleCode): string

// Get JSON path for database queries
public function getJsonPath(?string $currentChannelCode, ?string $currentLocaleCode): string

// Set value in product values array
public function setProductValue(
    mixed $value,
    array &$productValues,
    ?string $currentChannelCode = null,
    ?string $currentLocaleCode = null
): void

// Get validation rules
public function getValidationRules(
    ?string $currentChannelCode = null,
    ?string $currentLocaleCode = null,
    ?int $id = null,
    bool $withUniqueValidation = true
)
```

**Value Scope Resolution**:
- **Channel + Locale**: `channel_locale_specific->{channel}->{locale}->{code}`
- **Channel Only**: `channel_specific->{channel}->{code}`
- **Locale Only**: `locale_specific->{locale}->{code}`
- **Common/Global**: `common->{code}`

---

## 3. REPOSITORY PATTERN

### 3.1 Base Repository Class

**File**: `packages/Webkul/Core/src/Eloquent/Repository.php`

**Class**: `Webkul\Core\Eloquent\Repository extends BaseRepository`

**Implements**: `CacheableInterface`

**Traits**: `CacheableRepository`

**Features**:
- Extends Prettus Repository package
- Implements repository caching with configurable per-method control
- Methods: `findByField()`, `findOneByField()`, `findWhere()`, `findOneWhere()`, `resetModel()`

**Cache Configuration**:
```php
protected $cacheEnabled = false;
protected $cacheOnly = []; // Methods to cache only
protected $cacheExcept = []; // Methods to exclude from cache
```

### 3.2 Product Repository

**File**: `packages/Webkul/Product/src/Repositories/ProductRepository.php`

**Class**: `Webkul\Product\Repositories\ProductRepository extends Repository`

**Model**: `Webkul\Product\Contracts\Product`

**Constructor Injection**:
```php
public function __construct(
    protected AttributeRepository $attributeRepository,
    Container $container
)
```

**Key Methods**:
```php
public function create(array $data): Product
// Delegates to product type instance

public function update(array $data, $id, $attribute = 'id'): Product
// Updates and refreshes model

public function updateWithValues(array $data, int|string $id): Product
// Updates only values key without processing further values

public function updateStatus(bool $status, int $id): Product
```

### 3.3 Category Repository

**File**: `packages/Webkul/Category/src/Repositories/CategoryRepository.php`

**Class**: `Webkul\Category\Repositories\CategoryRepository extends Repository`

**Model**: `Webkul\Category\Contracts\Category`

**Constants**:
```php
const ADDITIONAL_VALUES_KEY = 'additional_data';
const LOCALE_VALUES_KEY = 'locale_specific';
const COMMON_VALUES_KEY = 'common';
```

**Key Methods**:
```php
public function getAll(array $params = [])
// Query builder with support for 'only_children', 'parent_id', 'limit'

public function create(array $data, bool $withoutFormattingValues = false): Category
```

### 3.4 Attribute Repository

**File**: `packages/Webkul/Attribute/src/Repositories/AttributeRepository.php`

**Class**: `Webkul\Attribute\Repositories\AttributeRepository extends Repository`

**Model**: `Webkul\Attribute\Contracts\Attribute`

**Constructor Injection**:
```php
public function __construct(
    protected AttributeOptionRepository $attributeOptionRepository,
    Container $container
)
```

**Key Methods**:
```php
public function create(array $data): Attribute
public function update(array $data, $id, $attribute = 'id'): Attribute
```

---

## 4. CONTRACTS / INTERFACES PATTERN

Each domain entity defines a contract (interface) that the Eloquent model implements. This enables dependency injection via Laravel's service container and makes the data layer swappable.

**Pattern**:
```
packages/Webkul/{Package}/src/Contracts/{Entity}.php    -- Interface
packages/Webkul/{Package}/src/Models/{Entity}.php       -- Eloquent implementation
packages/Webkul/{Package}/src/Providers/*ServiceProvider -- Binds contract to model
```

**Key Contracts**:
- `Webkul\Product\Contracts\Product`
- `Webkul\Category\Contracts\Category`
- `Webkul\Core\Contracts\Locale`
- `Webkul\Core\Contracts\Channel`
- `Webkul\Core\Contracts\Currency`
- `Webkul\Attribute\Contracts\Attribute`
- `Webkul\Core\Contracts\Database\Grammar`

---

## 5. TRANSLATABLE MODEL PATTERN

**File**: `packages/Webkul/Core/src/Eloquent/TranslatableModel.php`

**Class**: `Webkul\Core\Eloquent\TranslatableModel extends Model`

**Trait**: `Translatable` (from astrotomic/laravel-translatable)

**Key Methods**:
```php
protected function getLocalesHelper(): Locales
// Returns locale helper

protected function locale(): string
// Gets current locale with channel awareness

protected function isChannelBased(): bool
// Override to return true for channel-specific models

public function scopeWhereTranslationIn(
    Builder $query,
    string $translationField,
    $value,
    ?string $locale = null,
    string $method = 'whereHas'
): Builder
```

**Used By**: `Channel`, `Attribute`

**How It Works**:
- Models declare `$translatedAttributes` array listing fields that need translations
- A separate `*_translations` table is auto-managed (e.g. `channel_translations`, `attribute_translations`)
- Translation tables contain a `locale` column and the translatable fields
- Queries automatically resolve the correct locale's value

---

## 6. EXTERNAL SERVICE INTEGRATIONS

### 6.1 ElasticSearch Integration

**Main Query Builder**: `Webkul\ElasticSearch\ElasticSearchQuery`

**Methods**:
```php
public function where(array $clause): self // filter clause
public function whereNot(array $clause): self // must_not clause
public function orWhere(array $clause): self // should clause
public function must(array $clause): self // must clause
public function orderBy(array $sort): self // sort clause
public function aggregate(string $name, array $agg): self // aggregation
```

**Configuration**: `config/elasticsearch.php`
```php
'enabled' => env('ELASTICSEARCH_ENABLED', false),
'prefix' => env('ELASTICSEARCH_INDEX_PREFIX', env('APP_NAME')),
'connections' => [
    'default' => [
        'hosts' => [env('ELASTICSEARCH_HOST', 'http://localhost:9200')],
        'user' => env('ELASTICSEARCH_USER', null),
        'pass' => env('ELASTICSEARCH_PASS', null),
    ],
    'api' => [ /* API key auth */ ],
    'cloud' => [ /* Elastic Cloud */ ],
]
```

**Product Indexing Normalizer**: `Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer`

**Method**:
```php
public function normalize(array $attributeValues, array $options = []): array
// Normalizes product values structure for ES indexing
// Adds attribute type suffix: 'name-text', 'price-price', etc.
```

**Product Observer**: `Webkul\ElasticSearch\Observers\Product`

**Hooks**:
- `created()`: Index new products
- `updated()`: Update indexed products
- `deleted()`: Remove from index
- `enable()`: Enable observer
- `disable()`: Disable observer
- `isEnabled()`: Check status

### 6.2 MagicAI / OpenAI Integration

**OpenAI Service**: `Webkul\MagicAI\Services\OpenAI`

**Interface**: `Webkul\MagicAI\Contracts\LLMModelInterface`

**Constructor**:
```php
public function __construct(
    protected string $model,
    protected string $prompt,
    protected float $temperature,
    protected int $maxTokens,
    protected string $systemPrompt,
    protected bool $stream = false,
)
```

**Configuration**:
```php
public function setConfig(): void
{
    config([
        'openai.api_key' => core()->getConfigData('general.magic_ai.settings.api_key'),
        'openai.organization' => core()->getConfigData('general.magic_ai.settings.organization'),
    ]);
}
```

**Methods**:
```php
public function ask(): string // Get LLM response
public function images(array $options): array // Generate images with DALL-E
```

**Other LLM Providers**: Gemini, Groq, Ollama (same `LLMModelInterface`)

### 6.3 Mail Configuration

**File**: `config/mail.php`

**Default Mailer**: `env('MAIL_MAILER', 'smtp')`

**Mailers**: SMTP, SES, Mailgun, Postmark, Sendmail, Log, Array, Failover

**Global From Address**:
```php
'from' => [
    'address' => env('MAIL_FROM_ADDRESS'),
    'name' => env('MAIL_FROM_NAME'),
],
'admin' => [
    'address' => env('ADMIN_MAIL_ADDRESS'),
    'name' => env('ADMIN_MAIL_NAME', 'Admin'),
],
```

### 6.4 Queue Configuration

**File**: `config/queue.php`

**Default Driver**: `env('QUEUE_DRIVER', 'redis')`

**Drivers**:
- Sync (immediate)
- Database
- Beanstalkd
- SQS
- Redis (configured with 3 separate databases)

**Failed Jobs**: Stored with UUID tracking

### 6.5 Cache Configuration

**File**: `config/cache.php`

**Default Store**: `env('CACHE_DRIVER', 'file')`

**Stores**: APC, Array, Database, File, Memcached, Redis

**Redis Cache Connection**: Uses separate DB from default Redis

### 6.6 Filesystem Configuration

**File**: `config/filesystems.php`

**Default Disk**: `env('FILESYSTEM_DISK', 'public')`

**Disks**:
- **local**: `storage/app`
- **private**: `storage/app/private`
- **public**: `storage/app/public` (web accessible)
- **s3**: AWS S3 with environment config

---

## 7. PRODUCT VALUES JSON STRUCTURE

### 7.1 AbstractType Class Structure

**File**: `packages/Webkul/Product/src/Type/AbstractType.php`

**Constants**:
```php
const PRODUCT_VALUES_KEY = 'values';
const LOCALE_VALUES_KEY = 'locale_specific';
const CHANNEL_VALUES_KEY = 'channel_specific';
const CHANNEL_LOCALE_VALUES_KEY = 'channel_locale_specific';
const COMMON_VALUES_KEY = 'common';
const ASSOCIATION_VALUES_KEY = 'associations';
const CATEGORY_VALUES_KEY = 'categories';
const RELATED_ASSOCIATION_KEY = 'related_products';
const UP_SELLS_ASSOCIATION_KEY = 'up_sells';
const CROSS_SELLS_ASSOCIATION_KEY = 'cross_sells';
```

**Properties**:
```php
protected $product; // Product instance
protected $canBeCopied = true;
protected $hasVariants = false;
protected $isChildrenCalculated = false;
protected $skipAttributes = [];
protected $additionalViews = [];
```

**Constructor Injection**:
```php
public function __construct(
    protected AttributeRepository $attributeRepository,
    protected ProductRepository $productRepository,
    protected FileStorer $fileStorer,
)
```

**Methods**:
```php
public function create(array $data): Product
// Creates product with initialized values structure

public function update(array $data, $id, $attribute = 'id'): Product
// Updates product values with proper nesting

public function prepareProductValues(
    array $data,
    Product $product,
    ?string $currentLocaleCode = null,
    ?string $currentChannelCode = null
): array
// Formats/normalizes product values for storage
```

### 7.2 Complete Values Structure Example

```php
[
    'common' => [
        'sku' => 'PRODUCT-SKU',
        'name' => 'Product Name',
        'description' => 'Product Description',
        'price' => '99.99',
    ],
    'locale_specific' => [
        'en' => [
            'name' => 'English Name',
            'description' => 'English Description',
        ],
        'fr' => [
            'name' => 'French Name',
            'description' => 'French Description',
        ],
    ],
    'channel_specific' => [
        'default' => [
            'price' => '99.99',
            'cost' => '50.00',
        ],
        'secondary' => [
            'price' => '89.99',
            'cost' => '45.00',
        ],
    ],
    'channel_locale_specific' => [
        'default' => [
            'en' => [
                'name' => 'English Name for Default Channel',
                'meta_title' => 'Meta Title',
            ],
            'fr' => [
                'name' => 'French Name for Default Channel',
            ],
        ],
        'secondary' => [
            'en' => [
                'name' => 'English Name for Secondary Channel',
            ],
        ],
    ],
    'categories' => [1, 2, 3],
    'associations' => [
        'related_products' => [4, 5, 6],
        'up_sells' => [7, 8],
        'cross_sells' => [9, 10],
    ],
]
```

---

## 8. NESTED SET PATTERN FOR CATEGORIES

**Package**: `kalnoy/nestedset`

**Trait**: `NodeTrait` (applied to the `Category` model)

**How It Works**:
- Each category row has `_lft` and `_rgt` integer columns
- These columns encode the tree hierarchy so that all descendants of a node can be found with a single range query: `WHERE _lft > parent._lft AND _rgt < parent._rgt`
- Moving a node updates `_lft`/`_rgt` values across the tree atomically

**Category-Specific Builder**:
- `Webkul\Category\Database\Eloquent\Builder` extends `Kalnoy\Nestedset\QueryBuilder`
- Inherits tree traversal methods: `ancestors()`, `descendants()`, `siblings()`, `withDepth()`, etc.
- Adds custom pagination override

**Key Capabilities**:
- Efficient subtree queries without recursive SQL
- Automatic left/right value management on insert, move, and delete
- Depth calculation via `withDepth()` scope
- Root node detection via `isRoot()`

---

## 9. CUSTOM QUERY BUILDERS

### 9.1 Product Query Builder

**File**: `packages/Webkul/Product/src/Database/Eloquent/Builder.php`

**Class**: `Webkul\Product\Database\Eloquent\Builder extends BaseBuilder`

**Custom Method**:
```php
public function paginate(
    $perPage = null,
    $columns = ['*'],
    $pageName = 'page',
    $page = null
): LengthAwarePaginator
// Custom pagination with model's per-page setting
```

### 9.2 Category Query Builder

**File**: `packages/Webkul/Category/src/Database/Eloquent/Builder.php`

**Class**: `Webkul\Category\Database\Eloquent\Builder extends QueryBuilder`

**Base Class**: Extends Kalnoy NestedSet QueryBuilder

**Same Pagination**: Custom pagination method as Product builder

---

## 10. MIGRATION PATTERNS

### 10.1 Locale Migration

**File**: `packages/Webkul/Core/src/Database/Migrations/2018_07_10_055143_create_locales_table.php`

**Schema**:
```php
Schema::create('locales', function (Blueprint $table) {
    $table->increments('id');
    $table->string('code')->unique();
    $table->boolean('status')->default(0);
    $table->timestamps();
});
```

### 10.2 Currency Migration

**File**: `packages/Webkul/Core/src/Database/Migrations/2018_07_20_054502_create_currencies_table.php`

**Schema**:
```php
Schema::create('currencies', function (Blueprint $table) {
    $table->increments('id');
    $table->string('code');
    $table->string('symbol')->nullable();
    $table->boolean('status')->default(0);
    $table->integer('decimal')->unsigned()->default(2);
    $table->timestamps();
});
```

### 10.3 Channel Migration

**File**: `packages/Webkul/Core/src/Database/Migrations/2018_07_20_064849_create_channels_table.php`

**Schema**:
```php
Schema::create('channels', function (Blueprint $table) {
    $table->increments('id');
    $table->string('code');
    $table->integer('root_category_id')->nullable()->unsigned();
    $table->timestamps();
    $table->foreign('root_category_id')->references('id')->on('categories')->onDelete('set null');
});

// Junction tables with composite primary keys
Schema::create('channel_locales', function (Blueprint $table) {
    $table->integer('channel_id')->unsigned();
    $table->integer('locale_id')->unsigned();
    $table->primary(['channel_id', 'locale_id']);
    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
    $table->foreign('locale_id')->references('id')->on('locales')->onDelete('cascade');
});

Schema::create('channel_currencies', function (Blueprint $table) {
    $table->integer('channel_id')->unsigned();
    $table->integer('currency_id')->unsigned();
    $table->primary(['channel_id', 'currency_id']);
    $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
    $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
});
```

### 10.4 Product Migration

**File**: `packages/Webkul/Product/src/Database/Migrations/2018_07_27_065727_create_products_table.php`

**Schema**:
```php
Schema::create('products', function (Blueprint $table) {
    $table->increments('id');
    $table->string('sku')->unique();
    $table->string('type');
    $table->integer('parent_id')->unsigned()->nullable();
    $table->integer('attribute_family_id')->unsigned()->nullable();

    $table->json('values')->nullable(); // Main values JSON column
    $table->json('additional')->nullable();

    $table->timestamps();
    $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->onDelete('restrict');
});

// Additional indexes and self-referential foreign key
Schema::table('products', function (Blueprint $table) {
    $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
    $table->index('sku');
    $table->index('type');
    $table->index('attribute_family_id');
    $table->index('parent_id');
    $table->index(['attribute_family_id', 'parent_id'], 'attribute_family_parent_idx');
});

// Product relationships
Schema::create('product_relations', function (Blueprint $table) {
    $table->integer('parent_id')->unsigned();
    $table->integer('child_id')->unsigned();
    $table->foreign('parent_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('child_id')->references('id')->on('products')->onDelete('cascade');
    $table->unique(['parent_id', 'child_id']);
});

Schema::create('product_super_attributes', function (Blueprint $table) {
    $table->integer('product_id')->unsigned();
    $table->integer('attribute_id')->unsigned();
    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('restrict');
    $table->unique(['product_id', 'attribute_id']);
});
```

---

## 11. KEY ARCHITECTURAL INSIGHTS

### 11.1 Multi-Channel / Multi-Locale Support

Products support values per:
- **Global (Common)**: Single value across all channels and locales
- **Locale-only**: Different value per locale but same across channels
- **Channel-only**: Different value per channel but same across locales
- **Channel + Locale**: Different value per channel-locale combination

Each attribute has flags: `value_per_locale`, `value_per_channel` to determine nesting.

### 11.2 JSON Storage Strategy

- Main product attributes stored in single `values` JSON column
- Allows flexible schema without migrations
- Grammar layer abstracts JSON path access across MySQL/PostgreSQL/SQLite
- ElasticSearch denormalizes into document-based index for search

### 11.3 Database Driver Abstraction

- Grammar interface provides driver-specific SQL generation
- Supports JSON extraction across different databases
- Custom ordering, aggregation, and string operations abstracted

### 11.4 Translatable Pattern

- Uses astrotomic/laravel-translatable for automatic translation table management
- Models implement `TranslatableModel` with `$translatedAttributes`
- Separate translation tables created per model
- Example: `channels` + `channel_translations` for multilingual channel names

### 11.5 Nested Set for Categories

- Uses kalnoy/nestedset for hierarchical tree structure
- Manages `_lft` and `_rgt` columns automatically
- Enables efficient ancestor/descendant queries
- Custom builder extends NestedSet QueryBuilder

### 11.6 Repository Caching Layer

- Base repository provides caching over all query methods
- Cache enabled/disabled per repository and per method
- Uses Laravel's configurable cache system (Redis, File, etc.)

---

## Summary of Key Files & Classes

| Component | Namespace | File |
|-----------|-----------|------|
| Grammar Manager | `Webkul\Core\Helpers\Database` | `GrammarQueryManager.php` |
| Grammar Contract | `Webkul\Core\Contracts\Database` | `Grammar.php` |
| MySQL Grammar | `Webkul\Core\Helpers\Database\Grammars` | `MySQLGrammar.php` |
| PostgreSQL Grammar | `Webkul\Core\Helpers\Database\Grammars` | `PostgresGrammar.php` |
| SQLite Grammar | `Webkul\Core\Helpers\Database\Grammars` | `SQLiteGrammar.php` |
| Sequence Helper | `Webkul\Core\Helpers\Database` | `DatabaseSequenceHelper.php` |
| Product Model | `Webkul\Product\Models` | `Product.php` |
| Category Model | `Webkul\Category\Models` | `Category.php` |
| Locale Model | `Webkul\Core\Models` | `Locale.php` |
| Channel Model | `Webkul\Core\Models` | `Channel.php` |
| Currency Model | `Webkul\Core\Models` | `Currency.php` |
| Attribute Model | `Webkul\Attribute\Models` | `Attribute.php` |
| Base Repository | `Webkul\Core\Eloquent` | `Repository.php` |
| Product Repository | `Webkul\Product\Repositories` | `ProductRepository.php` |
| Category Repository | `Webkul\Category\Repositories` | `CategoryRepository.php` |
| Attribute Repository | `Webkul\Attribute\Repositories` | `AttributeRepository.php` |
| Translatable Model | `Webkul\Core\Eloquent` | `TranslatableModel.php` |
| AbstractType | `Webkul\Product\Type` | `AbstractType.php` |
| Product Builder | `Webkul\Product\Database\Eloquent` | `Builder.php` |
| Category Builder | `Webkul\Category\Database\Eloquent` | `Builder.php` |
| ElasticSearch Query | `Webkul\ElasticSearch` | `ElasticSearchQuery.php` |
| Product Normalizer | `Webkul\ElasticSearch\Indexing\Normalizer` | `ProductNormalizer.php` |
| Product Observer | `Webkul\ElasticSearch\Observers` | `Product.php` |
| OpenAI Service | `Webkul\MagicAI\Services` | `OpenAI.php` |
