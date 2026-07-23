<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeGroupTranslation as AttributeGroupTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

#[Fillable(['name'])]
#[WithoutTimestamps]
class AttributeGroupTranslation extends Model implements AttributeGroupTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['attributeGroup'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'name' => 'Name',
    ];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->attribute_group_id;
    }
}
