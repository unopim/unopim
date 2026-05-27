<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Contracts\Encryption\DecryptException;
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
     * Safely retrieve the API key, returning null when decryption fails
     * (e.g. APP_KEY changed after the platform was saved).
     */
    public function safeApiKey(): ?string
    {
        try {
            return $this->api_key;
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Check whether the stored API key can be decrypted with the current APP_KEY.
     * Returns the error message string when corrupted, or null when valid.
     */
    public function apiKeyError(): ?string
    {
        try {
            $this->api_key;

            return null;
        } catch (DecryptException $e) {
            return $e->getMessage();
        }
    }

    /**
     * Get the list of models as an array.
     */
    public function getModelListAttribute(): array
    {
        return array_map('trim', explode(',', $this->models));
    }
}
