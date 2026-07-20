<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Database\Factories\LocaleFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\User\Models\AdminProxy;

#[Appends([
    'name',
])]
#[Fillable([
    'code',
    'status',
])]
class Locale extends Model implements HistoryAuditable, LocaleContract
{
    use HasFactory;
    use HistoryTrait;

    /**
     * @var array<int, string>
     */
    protected array $historyTags = ['locale'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return LocaleFactory::new();
    }

    /**
     * Returns the users associated with this locale
     * users that have ui locale as this locale
     */
    public function user(): HasMany
    {
        return $this->hasMany(AdminProxy::modelClass(), 'ui_locale_id');
    }

    /**
     * Get the associated channels with this locale
     */
    public function channel(): BelongsToMany
    {
        return $this->belongsToMany(ChannelProxy::modelClass(), 'channel_locales', 'locale_id');
    }

    /**
     * Check locale linked to any channel or user
     */
    public function isLocaleBeingUsed(): bool
    {
        if ($this->user()->exists()) {
            return true;
        }

        return $this->channel()->exists();
    }

    /**
     * Accessor function for name property gets called whenever the name attribute is accessed
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => \Locale::getDisplayName($attributes['code'], app()->getLocale())
        )->shouldCache();
    }
}
