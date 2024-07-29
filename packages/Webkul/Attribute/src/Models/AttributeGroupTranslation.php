<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\Attribute\Contracts\AttributeGroupTranslation as AttributeGroupTranslationContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeGroupTranslation extends Model implements AttributeGroupTranslationContract, AuditableContract
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
}
