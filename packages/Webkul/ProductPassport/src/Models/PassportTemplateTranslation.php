<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplateTranslation as PassportTemplateTranslationContract;

class PassportTemplateTranslation extends Model implements HistoryContract, PassportTemplateTranslationContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['locale', 'name'];

    protected $historyTags = ['passport_template'];

    protected $historyTranslatableFields = ['name' => 'Name'];

    protected $auditExclude = ['passport_template_id', 'id'];

    public function getPrimaryModelIdForHistory(): int
    {
        return $this->passport_template_id;
    }
}
