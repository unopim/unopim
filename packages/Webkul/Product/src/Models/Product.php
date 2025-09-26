<?php

namespace Webkul\Product\Models;

use Exception;
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
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\BooleanPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Database\Eloquent\Builder;
use Webkul\Product\Database\Factories\ProductFactory;
use Webkul\Product\Presenters\ProductValuesPresenter;
use Webkul\Product\Type\AbstractType;

class Product extends Model implements HistoryAuditable, PresentableHistoryInterface, ProductContract
{
    use HasFactory, Visitable;
    use HistoryTrait;

    protected $historyTags = ['product'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'type',
        'attribute_family_id',
        'sku',
        'parent_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'additional' => 'array',
        'values'     => 'array',
    ];

    /**
     * The type of product.
     *
     * @var \Webkul\Product\Type\AbstractType
     */
    protected $typeInstance;

    /**
     * Get the product that owns the product.
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
     */
    public function variants(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    /**
     * Get type instance.
     *
     *
     * @throws \Exception
     */
    public function getTypeInstance(): AbstractType
    {
        if ($this->typeInstance) {
            return $this->typeInstance;
        }

        $this->typeInstance = app(config('product_types.'.$this->type.'.class'));

        if (! $this->typeInstance instanceof AbstractType) {
            throw new Exception("Please ensure the product type '{$this->type}' is configured in your application.");
        }

        $this->typeInstance->setProduct($this);

        return $this->typeInstance;
    }

    /**
     * The images that belong to the product.
     *
     * @return string
     */
    public function getBaseImageUrlAttribute()
    {
        $image = $this->images->first();

        return $image->url ?? null;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (! method_exists(static::class, $key)
            && ! in_array($key, [
                'pivot',
                'parent_id',
                'attribute_family_id',
            ])
            && ! isset($this->attributes[$key])
        ) {
            if (isset($this->id) && $this->attribute_family?->id) {
                $attribute = $this->checkInLoadedFamilyAttributes()->where('code', $key)->first();
                if ($attribute) {
                    $this->attributes[$key] = $this->getCustomAttributeValue($attribute);
                }

                return $this->getAttributeValue($key);
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * Retrieve product attributes.
     *
     * @param  Group  $group
     * @param  bool  $skipSuperAttribute
     *
     * @throws \Exception
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
     * Get an product attribute value.
     *
     * @return mixed
     */
    public function getCustomAttributeValue($attribute)
    {
        if (! $attribute) {
            return;
        }

        $locale = core()->getRequestedLocaleCodeInRequestedChannel();

        $channel = core()->getRequestedChannelCode();

        if (empty($this->attribute_values->count())) {
            $this->load('attribute_values');
        }

        if ($attribute->value_per_channel) {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('channel', core()->getDefaultChannelCode())
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('channel', $channel)
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        } else {
            if ($attribute->value_per_locale) {
                $attributeValue = $this->attribute_values
                    ->where('locale', $locale)
                    ->where('attribute_id', $attribute->id)
                    ->first();

                if (empty($attributeValue[$attribute->column_name])) {
                    $attributeValue = $this->attribute_values
                        ->where('locale', core()->getDefaultLocaleCodeFromDefaultChannel())
                        ->where('attribute_id', $attribute->id)
                        ->first();
                }
            } else {
                $attributeValue = $this->attribute_values
                    ->where('attribute_id', $attribute->id)
                    ->first();
            }
        }

        return $attributeValue[$attribute->column_name] ?? $attribute->default_value;
    }

    /**
     * Check in loaded family attributes.
     */
    public function checkInLoadedFamilyAttributes(): object
    {
        return core()->getSingletonInstance(AttributeRepository::class)
            ->getFamilyAttributes($this->attribute_family);
    }

    /**
     * Overrides the default Eloquent query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Webkul\Product\Database\Eloquent\Builder
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
     * Get all image attributes for the product
     */
    public function getImageAttributes()
    {
        return $this->attribute_family->customAttributes()->where('type', 'image')->get();
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
        $image = $this->getProductDisplayImage();

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
}
