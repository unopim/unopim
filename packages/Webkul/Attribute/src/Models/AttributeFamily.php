<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Contracts\AttributeFamily as AttributeFamilyContract;
use Webkul\Attribute\Database\Factories\AttributeFamilyFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Models\ProductProxy;

class AttributeFamily extends TranslatableModel implements AttributeFamilyContract, HistoryAuditable
{
    use HasFactory;
    use HistoryTrait;

    const ALLOWED_VARIANT_OPTION_TYPES = [
        'select',
    ];

    public $timestamps = false;

    public $translatedAttributes = ['name'];

    /** Tags for History */
    protected $historyTags = ['attributeFamily'];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [
        'attribute_family_group_mappings',
        'attribute_group_mappings',
    ];

    protected $fillable = [
        'code',
    ];

    protected $auditInclude = [
        'name',
        'code',
    ];

    /**
     * Get all the attributes for the attribute groups.
     */
    public function customAttributes()
    {
        return (AttributeProxy::modelClass())::join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', 'attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->join('attribute_families', 'attribute_family_group_mappings.attribute_family_id', '=', 'attribute_families.id')
            ->where('attribute_families.id', $this->id)
            ->select('attributes.*', 'attribute_groups.id as group_id');
    }

    /**
     * Get all the attributes for the attribute groups.
     */
    public function getCustomAttributesAttribute()
    {
        return $this->customAttributes()->get();
    }

    /**
     * Get all the attribute groups.
     */
    public function attributeFamilyGroupMappings()
    {
        return $this->hasMany(AttributeFamilyGroupMappingProxy::modelClass())
            ->orderBy('position');
    }

    public function familyGroups()
    {
        return $this->belongsToMany(AttributeGroupProxy::class::modelClass(), 'attribute_family_group_mappings');
    }

    /**
     * Get all the attributes for the attribute groups.
     */
    public function getConfigurableAttributes()
    {
        return $this->customAttributes()
            ->whereIn('attributes.type', self::ALLOWED_VARIANT_OPTION_TYPES)
            ->where('attributes.value_per_locale', 0)
            ->where('attributes.value_per_channel', 0)
            ->get();
    }

    /**
     * Get all the products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeFamilyFactory::new();
    }
}
