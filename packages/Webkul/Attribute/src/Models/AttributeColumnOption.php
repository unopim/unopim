<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Attribute\Database\Factories\AttributeColumnOptionFactory;
use Webkul\Core\Eloquent\TranslatableModel;

class AttributeColumnOption extends TranslatableModel
{
    use HasFactory;

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $fillable = [
        'code',
        'attribute_column_id',
    ];

    public function column()
    {
        return $this->belongsTo(AttributeColumnProxy::modelClass());
    }

    public function getForeignKey()
    {
        return 'option_id';
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeColumnOptionFactory::new();
    }
}
