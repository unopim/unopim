<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeFamilyTranslation as AttributeFamilyTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

#[Fillable(['name'])]
#[WithoutTimestamps]
class AttributeFamilyTranslation extends Model implements AttributeFamilyTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    protected $historyTags = ['attributeFamily'];

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
        return $this->attribute_family_id;
    }
}
