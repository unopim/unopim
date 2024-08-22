<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeGroupTranslation as AttributeGroupTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeGroupTranslation extends Model implements AttributeGroupTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    public $timestamps = false;

    protected $historyTags = ['attributeGroup'];

    protected $fillable = ['name'];

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
