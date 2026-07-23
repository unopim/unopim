<?php

namespace Webkul\Core;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;

class Core
{
    public $exchangeRateRepository;

    /**
     * The UnoPim version.
     *
     * @var string
     */
    const VERSION = 'master';

    /**
     * Current Channel.
     *
     * @var Channel
     */
    protected $currentChannel;

    /**
     * Default Channel.
     *
     * @var Channel
     */
    protected $defaultChannel;

    /**
     * Whether the default channel was set explicitly via setDefaultChannel(),
     * in which case it takes precedence over the configured channel code.
     */
    protected bool $defaultChannelExplicit = false;

    /**
     * Currency.
     *
     * @var Currency
     */
    protected $currentCurrency;

    /**
     * Base Currency.
     *
     * @var Currency
     */
    protected $baseCurrency;

    /**
     * Current Locale.
     *
     * @var Models\Locale
     */
    protected $currentLocale;

    /**
     * Exchange rates
     *
     * @var array
     */
    protected $exchangeRates = [];

    /**
     * Exchange rates
     *
     * @var array
     */
    protected $taxCategoriesById = [];

    /**
     * Stores singleton instances
     *
     * @var array
     */
    protected $singletonInstances = [];

    /**
     * Create a new instance.
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected LocaleRepository $localeRepository,
        protected CoreConfigRepository $coreConfigRepository
    ) {}

    /**
     * Get the version number of the UnoPim.
     */
    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Returns all channels.
     *
     * @return Collection
     */
    public function getAllChannels()
    {
        return $this->channelRepository->all();
    }

    /**
     * Set the current channel.
     */
    public function setCurrentChannel(Channel $channel): void
    {
        $this->currentChannel = $channel;
    }

    /**
     * Returns current channel models.
     *
     * @return Contracts\Channel
     */
    public function getCurrentChannel()
    {
        return $this->currentChannel ?? $this->getDefaultChannel();
    }

    /**
     * Returns default channel models.
     */
    public function getDefaultChannel(): ?Channel
    {
        // An explicitly set channel always wins. Otherwise the memoised channel is
        // reused only while it still matches the configured code, so a runtime change
        // to config('app.channel') is honoured and no stale channel leaks across
        // Octane requests.
        if ($this->defaultChannel
            && ($this->defaultChannelExplicit || $this->defaultChannel->code === config('app.channel'))
        ) {
            return $this->defaultChannel;
        }

        $this->defaultChannel = $this->channelRepository->findOneByField('code', config('app.channel'));

        if ($this->defaultChannel) {
            return $this->defaultChannel;
        }

        return $this->defaultChannel = $this->channelRepository->first();
    }

    /**
     * Set the default channel.
     */
    public function setDefaultChannel(Channel $channel): void
    {
        $this->defaultChannel = $channel;

        $this->defaultChannelExplicit = true;
    }

    /**
     * Returns the default channel code configured in `config/app.php`.
     */
    public function getDefaultChannelCode(): string
    {
        return $this->getDefaultChannel()?->code;
    }

    /**
     * Returns default locale code from default channel.
     *
     * Was previously hardcoded to 'en_US', which silently mislabelled every fallback translation on
     * an install whose channel does not carry English.
     */
    public function getDefaultLocaleCodeFromDefaultChannel(): string
    {
        return $this->getDefaultChannel()?->locales->first()?->code
            ?? config('app.locale');
    }

    /**
     * Get channel code from request.
     *
     * @return Contracts\Channel
     */
    public function getRequestedChannel()
    {
        $code = request()->query('channel');

        if ($code) {
            return $this->channelRepository->findOneByField('code', $code);
        }

        return $this->getCurrentChannel();
    }

    /**
     * Returns the code of the current channel.
     */
    public function getCurrentChannelCode(): ?string
    {
        return $this->getCurrentChannel()?->code;
    }

    /**
     * Get channel code from request.
     *
     * @param  bool  $fallback  optional
     * @return string
     */
    public function getRequestedChannelCode($fallback = true)
    {
        $channelCode = request()->input('channel');

        if (! $this->isValidScopeCode($channelCode)) {
            $channelCode = null;
        }

        if (! $fallback) {
            return $channelCode;
        }

        return $channelCode ?: ($this->currentChannel?->code ?: resolve(CatalogScope::class)->channelCode());
    }

    /**
     * Returns the channel name.
     */
    public function getChannelName($channel): string
    {
        return $channel->name ?? $channel->translate(app()->getLocale())->name ?? $channel->translate(config('app.fallback_locale'))->name;
    }

    /**
     * Return all locales.
     *
     * @return Collection
     */
    public function getAllLocales()
    {
        return $this->localeRepository->all()->sortBy('name');
    }

    /**
     * Return all active locales.
     *
     * @return Collection
     */
    public function getAllActiveLocales()
    {
        return $this->localeRepository->getActiveLocales();
    }

    /**
     * Return all locales that have translation files in the Admin package.
     *
     * @return Collection
     */
    public function getTranslatableLocales()
    {
        $langPath = base_path('packages/Webkul/Admin/src/Resources/lang');

        $availableDirs = array_map(basename(...), glob($langPath.'/*', GLOB_ONLYDIR) ?: []);

        return $this->localeRepository->all()
            ->filter(fn ($locale): bool => in_array($locale->code, $availableDirs))
            ->sortBy('name')
            ->values();
    }

    /**
     * Returns current locale.
     *
     * @return Contracts\Locale
     */
    public function getCurrentLocale()
    {
        if ($this->currentLocale) {
            return $this->currentLocale;
        }

        $this->currentLocale = $this->localeRepository->findOneByField('code', app()->getLocale());

        if (! $this->currentLocale) {
            $this->currentLocale = $this->localeRepository->findOneByField('code', config('app.fallback_locale'));
        }

        return $this->currentLocale;
    }

    /**
     * Get locale from request.
     *
     * @return Contracts\Locale|null
     */
    public function getRequestedLocale()
    {
        $code = request()->query('locale');

        if ($code) {
            return $this->localeRepository->findOneByField('code', $code);
        }

        return $this->getCurrentLocale();
    }

    /**
     * Get locale code from request. Here if you want to use admin locale,
     * you can pass it as an argument.
     *
     * @param  string  $localeKey  optional
     * @param  bool  $fallback  optional
     * @return string
     */
    public function getRequestedLocaleCode($localeKey = 'locale', $fallback = true)
    {
        $localeCode = request()->input($localeKey);

        if (! $this->isValidScopeCode($localeCode)) {
            $localeCode = null;
        }

        if (! $fallback) {
            return $localeCode;
        }

        return $localeCode ?: resolve(CatalogScope::class)->localeCode();
    }

    /**
     * Validate a request-supplied locale/channel code before it is used to build
     * raw SQL (e.g. JSON_EXTRACT paths). Anything outside this safe set is
     * rejected to prevent SQL injection via the scope parameters.
     */
    public function isValidScopeCode(mixed $code): bool
    {
        return is_string($code) && preg_match('/^[a-zA-Z0-9_-]+$/', $code) === 1;
    }

    /**
     * Check requested locale code in requested channel. If not found,
     * then set channel default locale code.
     *
     * @return string
     */
    public function getRequestedLocaleCodeInRequestedChannel()
    {
        $requestedLocaleCode = $this->getRequestedLocaleCode();

        $requestedChannel = $this->getRequestedChannel();

        if ($requestedChannel->locales->contains('code', $requestedLocaleCode)) {
            return $requestedLocaleCode;
        }

        return $requestedChannel->locales->first()?->code
            ?? $this->getDefaultLocaleCodeFromDefaultChannel();
    }

    /**
     * Returns all currencies.
     *
     * @return Collection
     */
    public function getAllCurrencies()
    {
        return $this->currencyRepository->all();
    }

    /**
     * Return all active currencies.
     *
     * @return Collection
     */
    public function getAllActiveCurrencies()
    {
        return $this->currencyRepository->getActiveCurrencies();
    }

    /**
     * Returns base channel's currency model.
     *
     * @return Contracts\Currency
     */
    public function getBaseCurrency()
    {
        if ($this->baseCurrency) {
            return $this->baseCurrency;
        }

        $this->baseCurrency = $this->currencyRepository->findOneByField('code', config('app.currency'));

        if (! $this->baseCurrency) {
            $this->baseCurrency = $this->currencyRepository->first();
        }

        return $this->baseCurrency;
    }

    /**
     * Returns base channel's currency code.
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->getBaseCurrency()?->code;
    }

    /**
     * Set currency.
     *
     * @param  string  $currencyCode
     */
    public function setCurrentCurrency($currencyCode): void
    {
        $this->currentCurrency = $this->currencyRepository->findOneByField('code', $currencyCode);
    }

    /**
     * Converts to base price.
     *
     * @param  float  $amount
     * @param  string  $targetCurrencyCode
     * @return string
     */
    public function convertToBasePrice($amount, $targetCurrencyCode = null)
    {
        $targetCurrency = $this->currencyRepository->findOneByField('code', $targetCurrencyCode);

        if (! $targetCurrency) {
            return $amount;
        }

        $exchangeRate = $this->exchangeRateRepository->findOneWhere([
            'target_currency' => $targetCurrency->id,
        ]);

        if (
            $exchangeRate === null
            || ! $exchangeRate->rate
        ) {
            return $amount;
        }

        return (float) $amount / $exchangeRate->rate;
    }

    /**
     * Format and convert price with currency symbol.
     *
     * @param  float  $amount
     * @return string
     */
    public function currency($amount = 0): string|false
    {
        if (is_null($amount)) {
            $amount = 0;
        }

        return $this->formatPrice($amount);
    }

    /**
     * Return currency symbol from currency code.
     *
     * @param  string|Contracts\Currency  $currency
     */
    public function currencySymbol($currency): string
    {
        $code = $currency instanceof Contracts\Currency ? $currency->code : $currency;

        $formatter = new \NumberFormatter(app()->getLocale().'@currency='.$code, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Format and convert price with currency symbol.
     *
     * @param  float  $price
     * @param  string|null  $currencyCode
     */
    public function formatPrice($price, $currencyCode = null): string|false
    {
        if (is_null($price)) {
            $price = 0;
        }

        $currencyCode ??= $this->getBaseCurrencyCode();

        $currency = $this->getAllCurrencies()->where('code', $currencyCode)->first();

        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $currency->decimal ?? 2);

        if (! $currency) {
            return $formatter->formatCurrency($price, $currencyCode);
        }

        if ($symbol = $currency->symbol) {
            if ($this->currencySymbol($currency) == $symbol) {
                return $formatter->formatCurrency($price, $currency->code);
            }

            $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $symbol);

            return $formatter->format($price);
        }

        return $formatter->formatCurrency($price, $currency->code);
    }

    /**
     * Format price with base currency symbol. This method also give ability to encode
     * the base currency symbol and its optional.
     *
     * @param  float  $price
     * @param  bool  $isEncoded
     * @return string
     */
    public function formatBasePrice($price, $isEncoded = false)
    {
        if (is_null($price)) {
            $price = 0;
        }

        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        if ($symbol = $this->getBaseCurrency()->symbol) {
            if ($this->currencySymbol($this->getBaseCurrencyCode()) == $symbol) {
                $content = $formatter->formatCurrency($price, $this->getBaseCurrencyCode());
            } else {
                $formatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, $symbol);

                $content = $formatter->format($price);
            }
        } else {
            $content = $formatter->formatCurrency($price, $this->getBaseCurrencyCode());
        }

        return $isEncoded ? htmlentities($content) : $content;
    }

    /**
     * Get channel timestamp, timestamp will be builded with channel timezone settings.
     *
     * @param  Contracts\Channel  $channel
     * @return int
     */
    public function channelTimeStamp($channel): int|false
    {
        $timezone = $channel->timezone;

        $currentTimezone = @date_default_timezone_get();

        @date_default_timezone_set($timezone);

        $date = date('Y-m-d H:i:s');

        @date_default_timezone_set($currentTimezone);

        return strtotime($date);
    }

    /**
     * Check whether sql date is empty.
     *
     * @param  string  $date
     */
    public function is_empty_date($date): bool
    {
        return preg_replace('#[ 0:-]#', '', $date) === '';
    }

    /**
     * Format date using current channel.
     *
     * @param  Carbon|string|null  $date
     */
    public function formatDate($date = null, string $format = 'd-m-Y H:i:s'): string
    {
        if (is_null($date)) {
            $date = Date::now();
        }

        if (is_string($date)) {
            $date = Date::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Format date to current user timezone.
     *
     * @param  Carbon|string|null  $date
     */
    public function formatDateWithTimeZone($date = null, string $format = 'd-m-Y H:i:s'): string
    {
        if (is_null($date)) {
            $date = Date::now();
        }

        $userTimeZone = auth('admin')?->user()?->timezone ?? config('app.timezone', 'UTC');

        if (is_string($date)) {
            $date = Date::parse($date);
        }

        $date->setTimezone($userTimeZone);

        return $date->format($format);
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param  string  $field
     * @param  int|string|null  $channel
     * @param  string|null  $locale
     * @return mixed
     */
    public function getConfigData($field, $channel = null, $locale = null)
    {
        if (empty($channel)) {
            $channel = $this->getRequestedChannelCode();
        }

        if (empty($locale)) {
            $locale = $this->getRequestedLocaleCode();
        }

        $coreConfig = $this->getCoreConfig($field, $channel, $locale);

        if (! $coreConfig) {
            return $this->getDefaultConfig($field);
        }

        return $coreConfig->value;
    }

    /**
     * Retrieve all countries.
     *
     * @return Collection
     */
    public function countries()
    {
        return DB::table('countries')->get();
    }

    /**
     * Return guest customer group.
     */
    public function getGuestCustomerGroup(): null
    {
        return null;
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     */
    public function xWeekRange($date, $day): string
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) === 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        }
        $end = (date('D', $ts) === 'Sat') ? $ts : strtotime('next saturday', $ts);

        return date('Y-m-d', $end);
    }

    /**
     * Method to sort through the acl items and put them in order.
     */
    public function sortItems(array $items): array
    {
        // Registering a child whose ancestor was filtered out by ACL seeds that
        // ancestor as a headless placeholder — no key, sort or name. Drop it:
        // an unpermitted parent must not surface its subtree.
        $items = array_filter($items, fn (array $item): bool => isset($item['key']));

        foreach ($items as &$item) {
            if (count($item['children'] ?? []) > 0) {
                $item['children'] = $this->sortItems($item['children']);
            }
        }

        unset($item);

        usort($items, fn (array $a, array $b): int => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        return $this->convertToAssociativeArray($items);
    }

    /**
     * Get config field.
     *
     * @param  string  $fieldName
     */
    public function getConfigField($fieldName): ?array
    {
        foreach (config('core') as $coreData) {
            if (! isset($coreData['fields'])) {
                continue;
            }

            foreach ($coreData['fields'] as $field) {
                $name = $coreData['key'].'.'.$field['name'];

                if ($name == $fieldName) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * Convert to associative array.
     */
    public function convertToAssociativeArray(array $items): array
    {
        foreach ($items as $key1 => $level1) {
            unset($items[$key1]);

            $items[$level1['key']] = $level1;

            if (count($level1['children']) === 0) {
                continue;
            }

            foreach ($level1['children'] as $key2 => $level2) {
                $temp2 = explode('.', $level2['key']);

                $finalKey2 = end($temp2);

                unset($items[$level1['key']]['children'][$key2]);

                $items[$level1['key']]['children'][$finalKey2] = $level2;

                if (count($level2['children']) === 0) {
                    continue;
                }

                foreach ($level2['children'] as $key3 => $level3) {
                    $temp3 = explode('.', $level3['key']);

                    $finalKey3 = end($temp3);

                    unset($items[$level1['key']]['children'][$finalKey2]['children'][$key3]);

                    $items[$level1['key']]['children'][$finalKey2]['children'][$finalKey3] = $level3;
                }
            }
        }

        return $items;
    }

    /**
     * Array set.
     *
     * @param  string|null  $key
     */
    public function array_set(array &$array, $key, array $value): array
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (
                ! isset($array[$key])
                || ! is_array($array[$key])
            ) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $finalKey = array_shift($keys);

        $array[$finalKey] = isset($array[$finalKey]) ? $this->arrayMerge($array[$finalKey], $value) : $value;

        return $array;
    }

    /**
     * Convert empty strings to null.
     */
    public function convertEmptyStringsToNull(array $array): array
    {
        foreach ($array as $key => $value) {
            if ($value == '' || $value == 'null') {
                $array[$key] = null;
            }
        }

        return $array;
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return object
     */
    public function getSingletonInstance($className)
    {
        if (array_key_exists($className, $this->singletonInstances)) {
            return $this->singletonInstances[$className];
        }

        return $this->singletonInstances[$className] = resolve($className);
    }

    /**
     * Returns a string as selector part for identifying elements in views.
     */
    public static function taxRateAsIdentifier(float $taxRate): string
    {
        return str_replace('.', '_', (string) $taxRate);
    }

    /**
     * Get tax category by its id.
     *
     * @param  int|string|null  $id
     * @return mixed
     */
    public function getTaxCategoryById($id)
    {
        if (empty($id)) {
            return;
        }

        if (array_key_exists($id, $this->taxCategoriesById)) {
            return $this->taxCategoriesById[$id];
        }

        return $this->taxCategoriesById[$id] = null;
    }

    /**
     * Get sender email details.
     */
    public function getSenderEmailDetails(): array
    {
        $senderName = $this->getConfigData('emails.configure.email_settings.sender_name') ?: config('mail.from.name');

        $senderEmail = $this->getConfigData('emails.configure.email_settings.shop_email_from') ?: config('mail.from.address');

        return [
            'name'  => $senderName,
            'email' => $senderEmail,
        ];
    }

    /**
     * Get Admin email details.
     */
    public function getAdminEmailDetails(): array
    {
        $adminName = $this->getConfigData('emails.configure.email_settings.admin_name')
            ?: (config('mail.admin.name')
            ?: config('mail.from.name'));

        $adminEmail = $this->getConfigData('emails.configure.email_settings.admin_email')
            ?: config('mail.admin.address');

        return [
            'name'  => $adminName,
            'email' => $adminEmail,
        ];
    }

    /**
     * Array merge.
     */
    protected function arrayMerge(array &$array1, array &$array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (
                is_array($value)
                && isset($merged[$key])
                && is_array($merged[$key])
            ) {
                $merged[$key] = $this->arrayMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Get core config values.
     *
     * @param  mixed  $field
     * @param  mixed  $channel
     * @param  mixed  $locale
     * @return mixed
     */
    protected function getCoreConfig($field, $channel, $locale)
    {
        $fields = $this->getConfigField($field);

        if (! empty($fields['channel_based'])) {
            if (! empty($fields['locale_based'])) {
                $coreConfigValue = $this->coreConfigRepository->findOneWhere([
                    'code'         => $field,
                    'channel_code' => $channel,
                    'locale_code'  => $locale,
                ]);
            } else {
                $coreConfigValue = $this->coreConfigRepository->findOneWhere([
                    'code'         => $field,
                    'channel_code' => $channel,
                ]);
            }
        } elseif (! empty($fields['locale_based'])) {
            $coreConfigValue = $this->coreConfigRepository->findOneWhere([
                'code'        => $field,
                'locale_code' => $locale,
            ]);
        } else {
            $coreConfigValue = $this->coreConfigRepository->findOneWhere([
                'code' => $field,
            ]);
        }

        return $coreConfigValue;
    }

    /**
     * Get default config.
     *
     * @param  string  $field
     * @return mixed
     */
    protected function getDefaultConfig($field)
    {
        $configFieldInfo = $this->getConfigField($field);

        $fields = explode('.', $field);

        array_shift($fields);

        $field = implode('.', $fields);

        return Config::get($field, $configFieldInfo['default'] ?? null);
    }

    /**
     * Get max upload size from the php.ini file.
     *
     * @return string
     */
    public function getMaxUploadSize(): string|false
    {
        return ini_get('upload_max_filesize');
    }

    /**
     * Get All timezones list with offset in name
     */
    public function getTimeZones(): array
    {
        $timezones = \DateTimeZone::listIdentifiers();

        $formattedTimezones = [];

        foreach ($timezones as $timezone) {
            $now = Date::now($timezone);

            $offset = $now->offset / 60;

            $formattedName = sprintf('%s (%+03d:%02d)', $timezone, $offset / 60, abs($offset % 60));

            $formattedTimezones[] = [
                'id'        => $timezone,
                'code'      => $timezone,
                'label'     => $formattedName,
            ];
        }

        return $formattedTimezones;
    }

    public function getCurrencyLabel(string $currency, ?string $language): ?string
    {
        $primaryLang = \Locale::getPrimaryLanguage($language);

        try {
            return Currencies::getName($currency, $primaryLang);
        } catch (MissingResourceException) {
            return '['.$currency.']';
        }
    }
}
