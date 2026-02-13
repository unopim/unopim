<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\AttributeOptionTranslation as AttributeOptionTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class AttributeOptionTranslation extends Model implements AttributeOptionTranslationContract, HistoryContract
{
    use BelongsToTenant;
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['label'];

    protected $historyTags = ['attribute'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'label' => 'Option Label',
    ];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->attribute_option_id;
    }
}
