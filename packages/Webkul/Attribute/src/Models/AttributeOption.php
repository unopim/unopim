<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Contracts\AttributeOption as AttributeOptionContract;
use Webkul\Attribute\Database\Factories\AttributeOptionFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeOption extends TranslatableModel implements AttributeOptionContract, HistoryContract
{
    use HasFactory;
    use HistoryTrait;

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $historyTags = ['attribute'];

    protected $fillable = [
        'code',
        'swatch_value',
        'sort_order',
        'attribute_id',
    ];

    /**
     * Append to the model attributes
     *
     * @var array
     */
    protected $appends = [
        'swatch_value_url',
    ];

    /**
     * Get the attribute that owns the attribute option.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }

    /**
     * Get image url for the swatch value url.
     */
    public function swatch_value_url()
    {
        if (
            $this->swatch_value
            && $this->attribute->swatch_type == 'image'
        ) {
            return Storage::url($this->swatch_value);
        }

        return null;
    }

    /**
     * Get image url for the product image.
     */
    public function getSwatchValueUrlAttribute()
    {
        return $this->swatch_value_url();
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeOptionFactory::new();
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->attribute_id;
    }
}
