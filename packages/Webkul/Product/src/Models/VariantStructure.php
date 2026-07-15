<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Product\Contracts\VariantStructure as VariantStructureContract;

class VariantStructure extends Model implements VariantStructureContract
{
    protected $fillable = [
        'attribute_family_id',
        'code',
        'name',
        'levels',
    ];

    protected $casts = [
        'levels' => 'integer',
    ];

    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }

    public function axes(): HasMany
    {
        return $this->hasMany(VariantStructureAxis::class, 'variant_structure_id')->orderBy('position');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(VariantStructureAttribute::class, 'variant_structure_id');
    }
}
