<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kalnoy\Nestedset\NodeTrait;
use Shetabit\Visitor\Traits\Visitable;
use Webkul\Category\Contracts\Category as CategoryContract;
use Webkul\Category\Database\Eloquent\Builder;
use Webkul\Category\Database\Factories\CategoryFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\JsonDataPresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Category extends Model implements CategoryContract, HistoryContract, PresentableHistoryInterface
{
    use HasFactory, NodeTrait, Visitable;
    use HistoryTrait;

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        '_lft',
        '_rgt',
        'id',
    ];

    protected $historyTags = ['category'];

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'parent_id',
    ];

    /**
     * Typecasts the defined columns into given types
     *
     * @var array
     */
    protected $casts = [
        'additional_data' => 'array',
    ];

    /**
     * Appends.
     *
     * @var array
     */
    protected $appends = ['name'];

    /**
     * Use fallback for category.
     */
    protected function useFallback(): bool
    {
        return true;
    }

    /**
     * Get fallback locale for category.
     */
    protected function getFallbackLocale(?string $locale = null): ?string
    {
        if ($fallback = core()->getDefaultLocaleCodeFromDefaultChannel()) {
            return $fallback;
        }
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }

    /**
     * Overrides the default Eloquent query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Webkul\Category\Database\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Accessor method for category name value
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => $this->additional_data['locale_specific'][core()->getRequestedLocaleCode()]['name'] ?? '['.$this->code.']'
        )->shouldCache();
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'additional_data' => JsonDataPresenter::class,
        ];
    }

    /**
     * Get the category that is the parent of this category.
     */
    public function parent_category(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }
}
