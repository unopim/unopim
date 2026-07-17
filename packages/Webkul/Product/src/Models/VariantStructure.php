<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Product\Contracts\VariantStructure as VariantStructureContract;

#[Fillable([
    'attribute_family_id',
    'code',
    'name',
    'levels',
])]
class VariantStructure extends Model implements VariantStructureContract
{
    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }

    /**
     * @return HasMany<VariantStructureAxis, $this>
     */
    public function axes(): HasMany
    {
        return $this->hasMany(VariantStructureAxis::class, 'variant_structure_id')->orderBy('position');
    }

    /**
     * @return HasMany<VariantStructureAttribute, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(VariantStructureAttribute::class, 'variant_structure_id');
    }

    protected function casts(): array
    {
        return [
            'levels' => 'integer',
        ];
    }
}
