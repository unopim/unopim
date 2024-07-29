<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeOptionTranslation as AttributeOptionTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeOptionTranslation extends Model implements AttributeOptionTranslationContract, HistoryContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['label'];

    protected $historyTags = ['attribute'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'label' => 'Option Label',
    ];
}
