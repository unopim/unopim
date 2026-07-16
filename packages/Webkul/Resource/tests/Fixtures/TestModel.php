<?php

namespace Webkul\Resource\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wk_resource_kit_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'label'];
}
