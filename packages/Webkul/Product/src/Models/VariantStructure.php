<?php

namespace Webkul\Product\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\VariantStructurePresenter;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Contracts\VariantStructure as VariantStructureContract;

#[Fillable([
    'attribute_family_id',
    'code',
    'name',
    'levels',
])]
class VariantStructure extends Model implements HistoryContract, PresentableHistoryInterface, VariantStructureContract
{
    use HistoryTrait;

    /** Tags for History */
    protected $historyTags = ['attributeFamily'];

    /** History is recorded from the family save path, as one entry per structure save. */
    protected $auditEvents = [];

    public function attribute_family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamilyProxy::modelClass());
    }

    /**
     * @return HasMany<VariantStructureAxis, $this>
     */
    public function axes(): HasMany
    {
        return $this->hasMany(VariantStructureAxis::class, 'variant_structure_id')->orderBy('position');
    }

    /**
     * @return HasMany<VariantStructureAttribute, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(VariantStructureAttribute::class, 'variant_structure_id');
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'common' => VariantStructurePresenter::class,
            'id'     => VariantStructurePresenter::class,
        ];
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->attribute_family_id;
    }

    protected function casts(): array
    {
        return [
            'levels' => 'integer',
        ];
    }
}
