<?php

namespace Webkul\MagicAI\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Webkul\MagicAI\Contracts\MagicAIPlatform as MagicAIPlatformContract;

#[Fillable([
    'label',
    'provider',
    'api_url',
    'api_key',
    'models',
    'extras',
    'is_default',
    'status',
])]
#[Hidden([
    'api_key',
])]
#[Table(name: 'magic_ai_platforms')]
class MagicAIPlatform extends Model implements MagicAIPlatformContract
{
    protected static function booted()
    {
        static::saving(function ($model): void {
            if ($model->is_default) {
                static::where('id', '!=', $model->id ?? 0)->update(['is_default' => false]);
            }
        });
    }

    #[Scope]
    protected function active($query)
    {
        return $query->where('status', true);
    }

    #[Scope]
    protected function default($query)
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
    protected function modelList(): Attribute
    {
        return Attribute::make(get: fn (): array => array_map(trim(...), explode(',', $this->models)));
    }

    protected function casts(): array
    {
        return [
            'extras'     => 'array',
            'is_default' => 'boolean',
            'status'     => 'boolean',
            'api_key'    => 'encrypted',
        ];
    }
}
