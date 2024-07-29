<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\CategoryFieldTranslation as CategoryFieldTranslationContract;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class CategoryFieldTranslation extends Model implements CategoryFieldTranslationContract, HistoryContract
{
    use HistoryTrait;

    public $timestamps = false;

    protected $fillable = ['name'];

    /** Tags for History */
    protected $historyTags = ['category_field'];

    /**
     * For Multilocale values history to display correctly
     */
    protected $historyTranslatableFields = ['name' => 'Name'];
}
