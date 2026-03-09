<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webkul\Measurement\Database\Factories\MeasurementFamilyFactory;


class MeasurementFamily extends Model implements HistoryAuditable
{
    use HasFactory, HistoryTrait;

    protected $fillable = [
        'code',
        'name',
        'labels',
        'standard_unit',
        'units',
        'symbol',
    ];

    protected $casts = [
        'units'  => 'array',
        'labels' => 'array',
    ];

    protected $historyTags = ['measurementFamily'];

    protected $historyColumns = [
        'code',
        'name',
        'labels',
        'standard_unit',
        'units',
        'symbol',
    ];

    protected static function newFactory()
    {
        return MeasurementFamilyFactory::new();
    }

    public function getUnitsArrayAttribute()
    {
        return $this->units ?? [];
    }
}
