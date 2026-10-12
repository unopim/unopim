<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplateField as PassportTemplateFieldContract;
use Webkul\ProductPassport\Enums\PassportFieldRole;
use Webkul\ProductPassport\Enums\PassportFieldSource;
use Webkul\ProductPassport\Enums\PassportFieldTier;

class PassportTemplateField extends TranslatableModel implements HistoryContract, PassportTemplateFieldContract
{
    use HistoryTrait;

    protected $historyTags = ['passport_template'];

    public $timestamps = false;

    public $translatedAttributes = ['label', 'fixed_value'];

    protected $fillable = [
        'passport_template_id',
        'passport_template_section_id',
        'code',
        'source_type',
        'attribute_id',
        'tier',
        'is_required',
        'role',
        'position',
    ];

    protected $auditExclude = ['passport_template_id', 'id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'source_type' => PassportFieldSource::class,
            'tier'        => PassportFieldTier::class,
            'role'        => PassportFieldRole::class,
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PassportTemplateProxy::modelClass(), 'passport_template_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(PassportTemplateSectionProxy::modelClass(), 'passport_template_section_id');
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass(), 'attribute_id');
    }

    public function getPrimaryModelIdForHistory(): int
    {
        return $this->passport_template_id;
    }
}
