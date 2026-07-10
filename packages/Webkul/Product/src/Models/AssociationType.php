<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Product\Contracts\AssociationType as AssociationTypeContract;

class AssociationType extends TranslatableModel implements AssociationTypeContract
{
    public $translatedAttributes = ['name'];

    protected $fillable = [
        'code',
        'status',
        'position',
        'is_user_defined',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(AssociationTypeFieldProxy::modelClass())->orderBy('position');
    }
}
