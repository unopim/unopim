<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\AssociationTypeFieldOption as AssociationTypeFieldOptionContract;

class AssociationTypeFieldOption extends TranslatableModel implements AssociationTypeFieldOptionContract, HistoryContract
{
    use HistoryTrait;

    /** Tags for History */
    protected $historyTags = ['association_type_field'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'association_type_field_id',
        'id',
    ];

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $fillable = [
        'code',
        'sort_order',
        'association_type_field_id',
    ];

    /**
     * Get the field that this option
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(AssociationTypeFieldProxy::modelClass());
    }

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
