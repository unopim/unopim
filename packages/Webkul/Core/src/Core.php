<?php

namespace Webkul\Core;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Customer\Models\CustomerGroup;

class Core
{
    public mixed $exchangeRateRepository = null;

    /**
     * The UnoPim version.
     *
     * @var string
     */
    const VERSION = '2.1.0';

    /**
     * Current Channel.
     */
    protected ?Channel $currentChannel = null;

    /**
     * Default Channel.
     */
    protected ?Channel $defaultChannel = null;

    /**
     * Currency.
     */
    protected ?Currency $currentCurrency = null;

    /**
     * Base Currency.
     */
    protected ?Currency $baseCurrency = null;

    /**
     * Current Locale.
     */
    protected ?Models\Locale $currentLocale = null;

    /**
     * Guest Customer Group
     */
    protected ?CustomerGroup $guestCustomerGroup = null;

    /**
     * Exchange rates
     */
    protected array $exchangeRates = [];

    /**
     * Exchange rates
     */
    protected array $taxCategoriesById = [];

    /**
     * Stores singleton instances
     */
    protected array $singletonInstances = [];

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
     */
    public function getAllChannels(): Collection
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
    public function getCurrentChannel(): ?Channel
    {
        return $this->currentChannel ?? $this->getDefaultChannel();
    }

    /**
     * Returns default channel models.
     *
     * @return Contracts\Channel
     */
    public function getDefaultChannel(): ?Channel
    {
        if ($this->defaultChannel instanceof Channel) {
            return $this->defaultChannel;
        }

        $this->defaultChannel = $this->channelRepository->findOneByField('code', config('app.channel'));

        if ($this->defaultChannel instanceof Channel) {
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
     */
    public function getDefaultLocaleCodeFromDefaultChannel(): string
    {
        return 'en_US';
    }

    /**
     * Get channel code from request.
     *
     * @return Contracts\Channel
     */
    public function getRequestedChannel(): ?Channel
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
    public function getRequestedChannelCode(bool $fallback = true): mixed
    {
        $channelCode = request()->input('channel');

        if (! $fallback) {
            return $channelCode;
        }

        return $channelCode ?: ($this->getCurrentChannelCode() ?: $this->getDefaultChannelCode());
    }

    /**
     * Returns the channel name.
     */
    public function getChannelName(mixed $channel): string
    {
        return $channel->name ?? $channel->translate(app()->getLocale())->name ?? $channel->translate(config('app.fallback_locale'))->name;
    }

    /**
     * Return all locales.
     */
    public function getAllLocales(): Collection
    {
        return $this->localeRepository->all()->sortBy('name');
    }

    /**
     * Return all active locales.
     */
    public function getAllActiveLocales(): Collection
    {
        return $this->localeRepository->getActiveLocales();
    }

    /**
     * Return all locales that have translation files in the Admin package.
     */
    public function getTranslatableLocales(): Collection
    {
        $langPath = base_path('packages/Webkul/Admin/src/Resources/lang');

        $availableDirs = array_map(basename(...), glob($langPath.'/*', GLOB_ONLYDIR) ?: []);

        return $this->localeRepository->all()
            ->filter(fn (mixed $locale) => in_array($locale->code, $availableDirs))
            ->sortBy('name')
            ->values();
    }

    /**
     * Returns current locale.
     *
     * @return Contracts\Locale
     */
    public function getCurrentLocale(): mixed
    {
        if ($this->currentLocale instanceof Models\Locale) {
            return $this->currentLocale;
        }

        $this->currentLocale = $this->localeRepository->findOneByField('code', app()->getLocale());

        if (! $this->currentLocale instanceof Models\Locale) {
            $this->currentLocale = $this->localeRepository->findOneByField('code', config('app.fallback_locale'));
        }

        return $this->currentLocale;
    }

    /**
     * Get locale from request.
     *
     * @return string
     */
    public function getRequestedLocale(): mixed
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
    public function getRequestedLocaleCode(string $localeKey = 'locale', bool $fallback = true): mixed
    {
        $localeCode = request()->input($localeKey);

        if (! $fallback) {
            return $localeCode;
        }

        return $localeCode ?: app()->getLocale();
    }

    /**
     * Check requested locale code in requested channel. If not found,
     * then set channel default locale code.
     *
     * @return string
     */
    public function getRequestedLocaleCodeInRequestedChannel(): mixed
    {
        $requestedLocaleCode = $this->getRequestedLocaleCode();

        $requestedChannel = $this->getRequestedChannel();

        if ($requestedChannel->locales->contains('code', $requestedLocaleCode)) {
            return $requestedLocaleCode;
        }

        return $requestedChannel->default_locale->code;
    }

    /**
     * Returns all currencies.
     */
    public function getAllCurrencies(): Collection
    {
        return $this->currencyRepository->all();
    }

    /**
     * Return all active currencies.
     */
    public function getAllActiveCurrencies(): Collection
    {
        return $this->currencyRepository->getActiveCurrencies();
    }

    /**
     * Returns base channel's currency model.
     *
     * @return Contracts\Currency
     */
    public function getBaseCurrency(): mixed
    {
        if ($this->baseCurrency instanceof Currency) {
            return $this->baseCurrency;
        }

        $this->baseCurrency = $this->currencyRepository->findOneByField('code', config('app.currency'));

        if (! $this->baseCurrency instanceof Currency) {
            $this->baseCurrency = $this->currencyRepository->first();
        }

        return $this->baseCurrency;
    }

    /**
     * Returns base channel's currency code.
     */
    public function getBaseCurrencyCode(): ?string
    {
        return $this->getBaseCurrency()?->code;
    }

    /**
     * Set currency.
     *
     * @param  string  $currencyCode
     */
    public function setCurrentCurrency(mixed $currencyCode): void
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
    public function convertToBasePrice(mixed $amount, mixed $targetCurrencyCode = null): mixed
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
     * @param  float  $price
     * @return string
     */
    public function currency(mixed $amount = 0): string|false
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
     * @return string
     */
    public function currencySymbol(mixed $currency): string|false
    {
        $code = $currency instanceof Contracts\Currency ? $currency->code : $currency;

        $formatter = new \NumberFormatter(app()->getLocale().'@currency='.$code, \NumberFormatter::CURRENCY);

        return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Format and convert price with currency symbol.
     *
     * @param  float  $price
     * @param  string (optional)  $currencyCode
     * @return string
     */
    public function formatPrice(mixed $price, mixed $currencyCode): string|false
    {
        if (is_null($price)) {
            $price = 0;
        }

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
     * @return string
     */
    public function formatBasePrice(mixed $price, bool $isEncoded = false): string|false
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
    public function channelTimeStamp(mixed $channel): int|false
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
    public function is_empty_date(mixed $date): bool
    {
        return preg_replace('#[ 0:-]#', '', $date) === '';
    }

    /**
     * Format date using current channel.
     *
     * @param  \Illuminate\Support\Carbon|string|null  $date
     */
    public function formatDate(mixed $date = null, string $format = 'd-m-Y H:i:s'): string
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Format date to current user timezone.
     *
     * @param  \Illuminate\Support\Carbon|string|null  $date
     */
    public function formatDateWithTimeZone(mixed $date = null, string $format = 'd-m-Y H:i:s'): string
    {
        if (is_null($date)) {
            $date = Carbon::now();
        }

        $userTimeZone = auth('admin')?->user()?->timezone ?? config('app.timezone', 'UTC');

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $date->setTimezone($userTimeZone);

        return $date->format($format);
    }

    /**
     * Retrieve information from payment configuration.
     *
     * @param  string  $field
     * @param  int|string|null  $channelId
     * @param  string|null  $locale
     */
    public function getConfigData(mixed $field, mixed $channel = null, mixed $locale = null): mixed
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
     */
    public function countries(): Collection
    {
        return DB::table('countries')->get();
    }

    /**
     * Return guest customer group.
     *
     * @return \Webkul\Customer\Contract\CustomerGroup
     */
    public function getGuestCustomerGroup(): mixed
    {
        return null;
    }

    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     */
    public function xWeekRange(mixed $date, mixed $day): string
    {
        $ts = strtotime($date);

        if (! $day) {
            $start = (date('D', $ts) === 'Sun') ? $ts : strtotime('last sunday', $ts);

            return date('Y-m-d', $start);
        } else {
            $end = (date('D', $ts) === 'Sat') ? $ts : strtotime('next saturday', $ts);

            return date('Y-m-d', $end);
        }
    }

    /**
     * Method to sort through the acl items and put them in order.
     */
    public function sortItems(array $items): array
    {
        foreach ($items as &$item) {
            if (count($item['children']) > 0) {
                $item['children'] = $this->sortItems($item['children']);
            }
        }

        usort($items, fn (mixed $a, mixed $b) => $a['sort'] <=> $b['sort']);

        return $this->convertToAssociativeArray($items);
    }

    /**
     * Get config field.
     */
    public function getConfigField(string $fieldName): ?array
    {
        foreach (config('core') as $coreData) {
            if (! isset($coreData['fields'])) {
                continue;
            }

            foreach ($coreData['fields'] as $field) {
                $name = $coreData['key'].'.'.$field['name'];

                if ($name === $fieldName) {
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
                $temp2 = explode('.', (string) $level2['key']);

                $finalKey2 = end($temp2);

                unset($items[$level1['key']]['children'][$key2]);

                $items[$level1['key']]['children'][$finalKey2] = $level2;

                if (count($level2['children']) === 0) {
                    continue;
                }

                foreach ($level2['children'] as $key3 => $level3) {
                    $temp3 = explode('.', (string) $level3['key']);

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
     * @param  array  $items
     * @param  string  $key
     * @param  string|int|float  $value
     * @return array
     */
    public function array_set(mixed &$array, mixed $key, mixed $value): mixed
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
     *
     * @param  array  $array1
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
     */
    public function getSingletonInstance(string $className): object
    {
        if (array_key_exists($className, $this->singletonInstances)) {
            return $this->singletonInstances[$className];
        }

        return $this->singletonInstances[$className] = app($className);
    }

    /**
     * Returns a string as selector part for identifying elements in views.
     */
    public static function taxRateAsIdentifier(float $taxRate): string
    {
        return str_replace('.', '_', (string) $taxRate);
    }

    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return object
     */
    public function getTaxCategoryById(mixed $id): mixed
    {
        if (empty($id)) {
            return null;
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
     */
    protected function getCoreConfig(mixed $field, mixed $channel, mixed $locale): mixed
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
     */
    protected function getDefaultConfig(string $field): mixed
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
            $now = Carbon::now($timezone);

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
