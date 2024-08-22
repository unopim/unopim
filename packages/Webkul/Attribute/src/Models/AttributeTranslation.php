<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeTranslation as AttributeTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeTranslation extends Model implements AttributeTranslationContract, HistoryContract
{
    use HistoryTrait;

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'id',
        'locale',
        'attribute_id',
    ];

    public $timestamps = false;

    protected $fillable = ['name'];

    protected $historyTags = ['attribute'];

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
        return $this->attribute_id;
    }
}
