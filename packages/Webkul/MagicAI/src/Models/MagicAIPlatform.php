<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\MagicAI\Contracts\MagicAIPlatform as MagicAIPlatformContract;

class MagicAIPlatform extends Model implements MagicAIPlatformContract
{
    protected $table = 'magic_ai_platforms';

    protected $fillable = [
        'label',
        'provider',
        'api_url',
        'api_key',
        'models',
        'extras',
        'is_default',
        'status',
    ];

    protected $casts = [
        'extras'     => 'array',
        'is_default' => 'boolean',
        'status'     => 'boolean',
        'api_key'    => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->is_default) {
                static::where('id', '!=', $model->id ?? 0)->update(['is_default' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the list of models as an array.
     */
    public function getModelListAttribute(): array
    {
        return array_map('trim', explode(',', $this->models));
    }
}
