<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeFamilyGroupMapping as AttributeFamilyGroupMappingContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\AttributeFamilyPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeFamilyGroupMapping extends Model implements AttributeFamilyGroupMappingContract, HistoryContract, PresentableHistoryInterface
{
    use HasFactory;
    use HistoryTrait;

    public $timestamps = false;

    /** Tags for History */
    protected $historyTags = ['attributeFamily'];

    protected $fillable = [
        'attribute_family_id',
        'attribute_group_id',
        'position',
    ];

    /**
     * Get the attributes that owns the attribute group.
     */
    public function customAttributes()
    {
        return $this->belongsToMany(AttributeProxy::modelClass(), 'attribute_group_mappings', 'attribute_family_group_id')
            ->withPivot('position')
            ->orderBy('pivot_position', 'asc');
    }

    /**
     * Get all the attribute groups.
     */
    public function attributeGroups()
    {
        return $this->belongsToMany(AttributeGroupProxy::modelClass(), 'attribute_family_group_mappings', 'attribute_group_id', null, 'attribute_group_id')
            ->orderBy('position')
            ->groupBy('id');
    }

    /**
     * {@inheritdoc}
     */
    public function generateTags(): array
    {
        return $this->historyTags;
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'common' => AttributeFamilyPresenter::class,
            'id'     => AttributeFamilyPresenter::class,
        ];
    }
}
