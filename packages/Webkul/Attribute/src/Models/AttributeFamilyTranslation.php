<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeFamilyTranslation as AttributeFamilyTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeFamilyTranslation extends Model implements AttributeFamilyTranslationContract, HistoryAuditable
{
    use HistoryTrait;

    public $timestamps = false;

    protected $historyTags = ['attributeFamily'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'name' => 'Name',
    ];

    protected $fillable = ['name'];

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
