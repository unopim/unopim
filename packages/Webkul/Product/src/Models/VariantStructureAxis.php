<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\AttributeProxy;

class VariantStructureAxis extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'variant_structure_id',
        'attribute_id',
        'level',
        'position',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }
}
