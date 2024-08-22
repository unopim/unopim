<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\CategoryFieldOptionTranslation as CategoryFieldOptionTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class CategoryFieldOptionTranslation extends Model implements CategoryFieldOptionTranslationContract, HistoryContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['label'];

    /** Tags for History */
    protected $historyTags = ['category_field'];

    /**
     * For Multilocale values history to display correctly
     */
    protected $historyTranslatableFields = ['label' => 'Option Label'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'category_field_option_id',
        'id',
    ];

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->category_field_option_id;
    }
}
