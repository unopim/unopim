<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplateSection as PassportTemplateSectionContract;

class PassportTemplateSection extends TranslatableModel implements HistoryContract, PassportTemplateSectionContract
{
    use HistoryTrait;

    protected $historyTags = ['passport_template'];

    public $timestamps = false;

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'passport_template_id',
        'code',
        'position',
    ];

    protected $auditExclude = ['passport_template_id', 'id'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PassportTemplateProxy::modelClass(), 'passport_template_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PassportTemplateFieldProxy::modelClass(), 'passport_template_section_id')
            ->orderBy('position');
    }

    public function getPrimaryModelIdForHistory(): int
    {
        return $this->passport_template_id;
    }
}
