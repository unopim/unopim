<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\ProductPassport\Contracts\PassportTemplate as PassportTemplateContract;

class PassportTemplate extends TranslatableModel implements HistoryContract, PassportTemplateContract
{
    use HistoryTrait;

    protected $historyTags = ['passport_template'];

    protected $historyProxyFields = ['fields', 'sections'];

    public $translatedAttributes = ['name'];

    protected $fillable = [
        'code',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(PassportTemplateFieldProxy::modelClass())->orderBy('position');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PassportTemplateSectionProxy::modelClass())->orderBy('position');
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeFamilyProxy::modelClass(),
            'passport_template_families',
            'passport_template_id',
            'attribute_family_id',
        );
    }
}
