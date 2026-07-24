<?php

namespace Webkul\Product\Models;

use Exception;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\BooleanPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Database\Eloquent\Builder;
use Webkul\Product\Database\Factories\ProductFactory;
use Webkul\Product\Presenters\ProductValuesPresenter;
use Webkul\Product\Type\AbstractType;

#[Fillable([
    'type',
    'attribute_family_id',
    'sku',
    'parent_id',
    'status',
    'variant_structure_id',
])]
class Product extends Model implements HistoryAuditable, PresentableHistoryInterface, ProductContract
{
    use HasFactory, Visitable;
    use HistoryTrait;

    protected $historyTags = ['product'];

    /**
     * The type of product.
     *
     * @var AbstractType
     */
    protected $typeInstance;

    /**
     * Get the product that owns the product.
     *
     * @return BelongsTo<Product, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /**
     * Get the product attribute family that owns the product.
     */
    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }

    /**
     * Get the variant structure that owns the product.
     */
    public function variantStructure(): BelongsTo
    {
        return $this->belongsTo(VariantStructureProxy::modelClass(), 'variant_structure_id');
    }

    /**
     * The super attributes that belong to the product.
     */
    public function super_attributes(): BelongsToMany
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'product_super_attributes');
    }

    /**
     * The images that belong to the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImageProxy::modelClass(), 'product_id')
            ->orderBy('position');
    }

    /**
     * Get the product variants that owns the product.
     *
     * @return HasMany<Product, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Get type instance.
     *
     *
     * @throws Exception
     */
    public function getTypeInstance(): AbstractType
    {
        if ($this->typeInstance) {
            return $this->typeInstance;
        }

        $this->typeInstance = resolve(config('product_types.'.$this->type.'.class'));

        if (! $this->typeInstance instanceof AbstractType) {
            throw new Exception("Please ensure the product type '{$this->type}' is configured in your application.");
        }

        $this->typeInstance->setProduct($this);

        return $this->typeInstance;
    }

    /**
     * The images that belong to the product.
     */
    protected function baseImageUrl(): Attribute
    {
        return Attribute::make(get: function () {
            $image = $this->images->first();

            return $image->url ?? null;
        });
    }

    /**
     * Retrieve product attributes.
     *
     * @param  Group  $group
     * @param  bool  $skipSuperAttribute
     *
     * @throws Exception
     */
    public function getEditableAttributes($group = null, $skipSuperAttribute = true): Collection
    {
        return $this->getTypeInstance()
            ->getEditableAttributes($group, $skipSuperAttribute);
    }

    public function completenessScores()
    {
        return $this->hasMany(ProductCompletenessScore::class, 'product_id');
    }

    public function getCompletenessScore($channelId = null, $select = ['locale_id', 'score', 'missing_count']): array
    {
        $channelId = $channelId ?: core()->getRequestedChannel()?->id;

        if (! $channelId) {
            return [];
        }

        $scores = [];

        foreach ($this->completenessScores()->where('channel_id', $channelId)->select($select)->get() as $score) {
            $scores[$score->locale_id] = $score->toArray();
        }

        return $scores;
    }

    public function getCompletenessAttributes($channelId = null)
    {
        $channelId = $channelId ?: core()->getRequestedChannel()?->id;

        if (! $channelId || ! $this->attribute_family_id) {
            return [];
        }

        return CompletenessSetting::where('family_id', $this->attribute_family_id)
            ->where('channel_id', $channelId)
            ->get();
    }

    /**
     * Resolve this product's `values` across its ancestor chain (read-time
     * variant inheritance). Returns the effective values array: the product's
     * own values overlaid on every ancestor's, root -> leaf.
     */
    public function resolvedValues(): array
    {
        return resolve(VariantValueResolver::class)->resolve($this);
    }

    /**
     * Overrides the default Eloquent query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'values' => ProductValuesPresenter::class,
            'status' => BooleanPresenter::class,
        ];
    }

    /**
     * Get all image attributes for the product.
     *
     * Image attributes are family-level, so the result is memoised per family for the
     * request to avoid one join per product in normalizeWithImage() loops.
     */
    public function getImageAttributes()
    {
        $memoKey = "product_image_attributes.{$this->attribute_family_id}";

        $memo = request()->attributes;

        if ($memo->has($memoKey)) {
            return $memo->get($memoKey);
        }

        $imageAttributes = $this->attribute_family->customAttributes()->where('type', 'image')->get();

        $memo->set($memoKey, $imageAttributes);

        return $imageAttributes;
    }

    /**
     * Find the first image which has value in the product to be displayed as display image
     * This will be the image to be displayed as product thumbnail from product values
     */
    public function getProductDisplayImage(?string $currentChannelCode = null, ?string $currentLocaleCode = null, mixed $imageAttributes = null): ?string
    {
        $imageAttributes ??= $this->getImageAttributes();

        $productImage = null;

        $productValues = $this->values;

        $currentChannelCode ??= core()->getRequestedChannelCode();

        $currentLocaleCode ??= core()->getRequestedLocaleCode();

        foreach ($imageAttributes as $attribute) {
            if ($productImage = $attribute->getValueFromProductValues($productValues, $currentChannelCode, $currentLocaleCode)) {
                break;
            }
        }

        return $productImage;
    }

    /**
     * Normalize product data with product image displaye url
     */
    public function normalizeWithImage(?string $currentChannelCode = null, ?string $currentLocaleCode = null, mixed $imageAttributes = null): array
    {
        $image = $this->getProductDisplayImage($currentChannelCode, $currentLocaleCode, $imageAttributes);

        $image = $image ? Storage::url($image) : null;

        return [
            'id'              => $this->id,
            'sku'             => $this->sku,
            'parent'          => $this->parent,
            'values'          => $this->values,
            'additional_data' => $this->additional_data,
            'image'           => $image,
        ];
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'additional' => 'array',
            'values'     => 'array',
        ];
    }
}
