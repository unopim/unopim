<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\AssociationTypeFieldTranslation as AssociationTypeFieldTranslationContract;

class AssociationTypeFieldTranslation extends Model implements AssociationTypeFieldTranslationContract, HistoryContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['name'];

    /** Tags for History */
    protected $historyTags = ['association_type_field'];

    /**
     * For Multilocale values history to display correctly
     */
    protected $historyTranslatableFields = ['name' => 'Name'];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->association_type_field_id;
    }
}
