<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\Attribute\Contracts\AttributeFamilyTranslation as AttributeFamilyTranslationContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeFamilyTranslation extends Model implements AttributeFamilyTranslationContract, AuditableContract
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
}
