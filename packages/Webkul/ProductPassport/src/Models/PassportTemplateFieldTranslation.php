<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplateFieldTranslation as PassportTemplateFieldTranslationContract;

class PassportTemplateFieldTranslation extends Model implements HistoryContract, PassportTemplateFieldTranslationContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['locale', 'label', 'fixed_value'];

    protected $historyTags = ['passport_template'];

    protected $historyTranslatableFields = [
        'label'       => 'Label',
        'fixed_value' => 'Fixed value',
    ];

    protected $auditExclude = ['passport_template_field_id', 'id'];
}
