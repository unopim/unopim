<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Category\Contracts\CategoryFieldOption as CategoryFieldOptionProxy;
use Webkul\Category\Database\Factories\CategoryFieldOptionFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class CategoryFieldOption extends TranslatableModel implements CategoryFieldOptionProxy, HistoryContract
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    /** Tags for History */
    protected $historyTags = ['category_field'];

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'category_field_id',
        'id',
    ];

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $fillable = [
        'code',
        'sort_order',
        'category_field_id',
    ];

    /**
     * Get the field that this option
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(CategoryFieldProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model
     */
    public static function newFactory(): Factory
    {
        return CategoryFieldOptionFactory::new();
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->category_field_id;
    }
}
