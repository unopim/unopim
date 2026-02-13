<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Symfony\Component\Intl\Currencies;
use Webkul\Core\Contracts\Currency as CurrencyContract;
use Webkul\Core\Database\Factories\CurrencyFactory;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Currency extends Model implements AuditableContract, CurrencyContract
{
    use BelongsToTenant, Auditable;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'symbol',
        'decimal',
        'status',
    ];

    /**
     * Extra fields/properties that do not exist in the table and are added to the object
     */
    protected $appends = [
        'name',
    ];

    /**
     * Set currency code in capital
     */
    public function setCodeAttribute($code): void
    {
        $this->attributes['code'] = strtoupper($code);
    }

    /**
     * Get the exchange rate associated with the currency.
     */
    public function exchange_rate(): HasOne
    {
        return $this->hasOne(CurrencyExchangeRateProxy::modelClass(), 'target_currency');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }

    /**
     * Get the associated channels with this currency
     */
    public function channel(): BelongsToMany
    {
        return $this->belongsToMany(ChannelProxy::modelClass(), 'channel_currencies');
    }

    /**
     * checks whether this currency is linked to any channel
     */
    public function isCurrencyBeingUsed(): bool
    {
        return $this->channel()?->get()?->first()?->exists() ?? false;
    }

    /**
     * Accessor function for name property gets called whenever the name attribute is used
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                try {
                    return Currencies::getName($attributes['code'], \Locale::getPrimaryLanguage(app()->getLocale()));
                } catch (\Exception $e) {
                    return $attributes['code'];
                }
            }
        )->shouldCache();
    }
}
