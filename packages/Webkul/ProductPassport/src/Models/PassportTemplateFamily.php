<?php

namespace Webkul\ProductPassport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\ProductPassport\Contracts\PassportTemplateFamily as PassportTemplateFamilyContract;

class PassportTemplateFamily extends Model implements PassportTemplateFamilyContract
{
    public $timestamps = false;

    protected $table = 'passport_template_families';

    protected $fillable = [
        'passport_template_id',
        'attribute_family_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PassportTemplateProxy::modelClass(), 'passport_template_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass(), 'attribute_family_id');
    }
}
