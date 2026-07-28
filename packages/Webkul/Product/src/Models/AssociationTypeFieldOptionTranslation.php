<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\AssociationTypeFieldOptionTranslation as AssociationTypeFieldOptionTranslationContract;

class AssociationTypeFieldOptionTranslation extends Model implements AssociationTypeFieldOptionTranslationContract, HistoryContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['label'];

    /** Tags for History */
    protected $historyTags = ['association_type'];

    /**
     * For Multilocale values history to display correctly
     */
    protected $historyTranslatableFields = ['label' => 'Option Label'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'association_type_field_option_id',
        'id',
    ];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        $fieldId = AssociationTypeFieldOptionProxy::modelClass()::query()
            ->whereKey($this->association_type_field_option_id)
            ->value('association_type_field_id');

        return (int) AssociationTypeFieldProxy::modelClass()::query()
            ->whereKey($fieldId)
            ->value('association_type_id');
    }
}
