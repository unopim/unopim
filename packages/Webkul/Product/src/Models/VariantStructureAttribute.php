<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\AttributeProxy;

#[Fillable([
    'variant_structure_id',
    'attribute_id',
    'level',
])]
#[WithoutTimestamps]
class VariantStructureAttribute extends Model
{
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }
}
