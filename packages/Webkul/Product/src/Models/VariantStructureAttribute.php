<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\AttributeProxy;

class VariantStructureAttribute extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'variant_structure_id',
        'attribute_id',
        'level',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }
}
