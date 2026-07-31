<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplateSectionTranslation as PassportTemplateSectionTranslationContract;

class PassportTemplateSectionTranslation extends Model implements HistoryContract, PassportTemplateSectionTranslationContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['locale', 'name'];

    protected $historyTags = ['passport_template'];

    protected $historyTranslatableFields = ['name' => 'Name'];

    protected $auditExclude = ['passport_template_section_id', 'id'];
}
