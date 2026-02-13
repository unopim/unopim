<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Attribute\Contracts\AttributeGroup as AttributeGroupContract;
use Webkul\Attribute\Database\Factories\AttributeGroupFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class AttributeGroup extends TranslatableModel implements AttributeGroupContract, HistoryAuditable
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    public $timestamps = false;

    public $translatedAttributes = ['name'];

    protected $historyTags = ['attributeGroup'];

    protected $fillable = [
        'code',
        'column',
        'position',
    ];

    /**
     * Get all the attribute groups.
     */
    public function customAttributes($familyId)
    {
        return (AttributeProxy::modelClass())::join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', 'attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->where('attribute_family_group_mappings.attribute_group_id', $this->id)
            ->where('attribute_family_group_mappings.attribute_family_id', $familyId)
            ->orderBy('attribute_group_mappings.position', 'asc')
            ->select('attributes.*', 'attribute_groups.id as group_id')->get();
    }

    /**
     * Get all the group mapping with mapping.
     */
    public function groupMappings()
    {
        return $this->hasMany(AttributeFamilyGroupMappingProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeGroupFactory::new();
    }
}
