<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeColumnOptionTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['label'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'label' => 'Column Option Label',
    ];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->option_id;
    }
}
