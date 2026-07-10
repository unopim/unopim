<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Contracts\AssociationTypeTranslation as AssociationTypeTranslationContract;

class AssociationTypeTranslation extends Model implements AssociationTypeTranslationContract
{
    public $timestamps = false;

    protected $fillable = ['locale', 'name'];
}
