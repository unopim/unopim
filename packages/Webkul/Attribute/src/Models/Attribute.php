<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Database\Factories\AttributeFactory;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Rules\BooleanString;
use Webkul\Core\Rules\Decimal;
use Webkul\Core\Rules\FileOrImageValidValue;
use Webkul\Core\Rules\Slug;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Validator\Rule\AttributeOptionRule;
use Webkul\Product\Validator\Rule\Elasticsearch\UniqueAttributeValue;

class Attribute extends TranslatableModel implements AttributeContract, HistoryContract
{
    use HasFactory;
    use HistoryTrait;

    const TEXT_TYPE = 'text';

    const TEXTAREA_TYPE = 'textarea';

    const BOOLEAN_FIELD_TYPE = 'boolean';

    const PRICE_FIELD_TYPE = 'price';

    const SELECT_FIELD_TYPE = 'select';

    const MULTISELECT_FIELD_TYPE = 'multiselect';

    const DATETIME_FIELD_TYPE = 'datetime';

    const DATE_FIELD_TYPE = 'date';

    const CHECKBOX_FIELD_TYPE = 'checkbox';

    const FILE_ATTRIBUTE_TYPE = 'file';

    const IMAGE_ATTRIBUTE_TYPE = 'image';

    const GALLERY_ATTRIBUTE_TYPE = 'gallery';

    public $translatedAttributes = ['name'];

    protected $historyTags = ['attribute'];

    protected $fillable = [
        'code',
        'type',
        'enable_wysiwyg',
        'position',
        'is_required',
        'is_unique',
        'validation',
        'regex_pattern',
        'value_per_locale',
        'value_per_channel',
        'is_filterable',
        'ai_translate',
    ];

    const NON_DELETABLE_ATTRIBUTE_CODE = 'sku';

    /**
     * These columns history will not be generated
     */
    protected $auditExclude = [
        'id',
    ];

    /**
     * Get the options.
     */
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOptionProxy::modelClass());
    }

    /**
     * Returns attribute validation rules
     *
     * @return string
     */
    public function getValidationsField()
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required: true';
        }

        if ($this->type == 'price') {
            $validations[] = 'decimal: true';
        }

        if ($this->type == 'file') {
            $retVal = core()->getConfigData('catalog.products.attribute.file_attribute_upload_size') ?? '2048';

            if ($retVal) {
                $validations[] = 'size:'.$retVal;
            }
        }

        if ($this->type == 'image') {
            $retVal = core()->getConfigData('catalog.products.attribute.image_attribute_upload_size') ?? '2048';

            if ($retVal) {
                $validations[] = 'size:'.$retVal.', mimes: ["image/bmp", "image/jpeg", "image/jpg", "image/png", "image/webp"]';
            }
        }

        if ($this->validation) {
            $validations[] = match ($this->validation) {
                'regex'  => 'regex: "'.$this->regex_pattern.'"',
                'number' => 'numeric: true',
                default  => $this->validation.': true'
            };
        }

        $validations = '{ '.implode(', ', array_filter($validations)).' }';

        return $validations;
    }

    /**
     * Validation rules for validator
     * used while validating product values
     */
    public function getValidationRules(?string $currentChannelCode = null, ?string $currentLocaleCode = null, ?int $id = null, bool $withUniqueValidation = true)
    {
        $validations = $this->fieldTypeValidations();

        $validations[] = $this->is_required ? 'required' : 'nullable';

        if ($this->type == 'price') {
            $validations[] = "regex:/^\d+(\.\d+)?$/";
        }

        if ($this->is_unique && $this->code !== 'sku' && $withUniqueValidation) {
            if (config('elasticsearch.enabled')) {
                $rule = new UniqueAttributeValue($this->code, $id);
            } else {
                $path = $this->getJsonPath($currentChannelCode, $currentLocaleCode);

                $rule = "unique:products,values->{$path}";

                if ($id) {
                    $rule .= ",{$id}";
                }
            }

            $validations[] = $rule;
        }

        if ($this->validation) {
            $validations[] = match ($this->validation) {
                'regex'   => 'regex: "'.$this->regex_pattern.'"',
                'number'  => 'numeric',
                'decimal' => new Decimal,
                default   => $this->validation
            };
        }

        if ($this->code === 'sku') {
            $validations[] = new Slug;
        }

        return $validations;
    }

    /**
     * Returns field validation rules for API and internal functions
     */
    public function getValidationsOnlyMedia(): array
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required';
        }

        if ($this->type === 'file') {
            $validations[] = 'file';
            $validations[] = 'max:'.(core()->getConfigData('catalog.products.attribute.file_attribute_upload_size') ?? '2048');
        }

        if ($this->type === 'image') {
            $validations[] = 'file';
            $validations[] = 'mimes:bmp,jpeg,jpg,png';
            $retVal = core()->getConfigData('catalog.products.attribute.image_attribute_upload_size') ?? '2048';

            if ($retVal) {
                $validations[] = 'max:'.$retVal.'';
            }
        }

        $validations[] = new FileOrImageValidValue(isImage: $this->type != AttributeTypes::FILE_ATTRIBUTE_TYPE, isMultiple: $this->type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE);

        return $validations;
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeFactory::new();
    }

    /**
     * Checks if this attribute is based on locale
     */
    public function isLocaleBasedAttribute(): bool
    {
        return (bool) $this->value_per_locale;
    }

    /**
     * Checks if this attribute is based on channel
     */
    public function isChannelBasedAttribute(): bool
    {
        return (bool) $this->value_per_channel;
    }

    /**
     * Is attribute usable in product grid or not
     */
    public function isUsableInGrid(): bool
    {
        return (bool) $this->usable_in_grid;
    }

    /**
     * Checks if this attribute is based on both locale and channel
     */
    public function isLocaleAndChannelBasedAttribute(): bool
    {
        return (bool) ($this->isLocaleBasedAttribute() && $this->isChannelBasedAttribute());
    }

    /**
     * Return value from product values array
     */
    public function getValueFromProductValues(
        array $values,
        string $currentChannelCode,
        string $currentLocaleCode
    ): mixed {
        if ($this->isLocaleAndChannelBasedAttribute()) {
            return $values['channel_locale_specific'][$currentChannelCode][$currentLocaleCode][$this->code] ?? null;
        }

        if ($this->isChannelBasedAttribute()) {
            return $values['channel_specific'][$currentChannelCode][$this->code] ?? null;
        }

        if ($this->isLocaleBasedAttribute()) {
            return $values['locale_specific'][$currentLocaleCode][$this->code] ?? null;
        }

        return $values['common'][$this->code] ?? null;
    }

    /**
     * Get the Flat name for this attribute used in product form for input
     */
    public function getFlatAttributeName(string $currentChannelCode, string $currentLocaleCode): string
    {
        if ($this->isLocaleAndChannelBasedAttribute()) {
            return '.channel_locale_specific.'.$currentChannelCode.'.'.$currentLocaleCode.'.'.$this->code;
        }

        if ($this->isChannelBasedAttribute()) {
            return '.channel_specific.'.$currentChannelCode.'.'.$this->code;
        }

        if ($this->isLocaleBasedAttribute()) {
            return '.locale_specific.'.$currentLocaleCode.'.'.$this->code;
        }

        return '.common.'.$this->code;
    }

    /**
     * Get the formatted Attribute input name to be used in product edit form
     */
    public function getAttributeInputFieldName(string $currentChannelCode, string $currentLocaleCode): string
    {
        if ($this->isLocaleAndChannelBasedAttribute()) {
            return '[channel_locale_specific]['.$currentChannelCode.']['.$currentLocaleCode.']['.$this->code.']';
        }

        if ($this->isChannelBasedAttribute()) {
            return '[channel_specific]['.$currentChannelCode.']['.$this->code.']';
        }

        if ($this->isLocaleBasedAttribute()) {
            return '[locale_specific]['.$currentLocaleCode.']['.$this->code.']';
        }

        return '[common]['.$this->code.']';
    }

    /**
     * Get path used to access value in json column of sql
     */
    public function getJsonPath(?string $currentChannelCode, ?string $currentLocaleCode): string
    {
        if ($this->isLocaleAndChannelBasedAttribute()) {
            return 'channel_locale_specific->'.$currentChannelCode.'->'.$currentLocaleCode.'->'.$this->code;
        }

        if ($this->isChannelBasedAttribute()) {
            return 'channel_specific->'.$currentChannelCode.'->'.$this->code;
        }

        if ($this->isLocaleBasedAttribute()) {
            return 'locale_specific->'.$currentLocaleCode.'->'.$this->code;
        }

        return 'common->'.$this->code;
    }

    /**
     * check if possible to delete this attribute
     */
    public function canBeDeleted()
    {
        return $this->code !== self::NON_DELETABLE_ATTRIBUTE_CODE;
    }

    /**
     * set attribute value in product values
     */
    public function setProductValue(
        mixed $value,
        array &$productValues,
        ?string $currentChannelCode = null,
        ?string $currentLocaleCode = null
    ): void {
        if ($this->isLocaleAndChannelBasedAttribute()) {
            $productValues['channel_locale_specific'][$currentChannelCode][$currentLocaleCode][$this->code] = $value;

            return;
        }

        if ($this->isChannelBasedAttribute()) {
            $productValues['channel_specific'][$currentChannelCode][$this->code] = $value;

            return;
        }

        if ($this->isLocaleBasedAttribute()) {
            $productValues['locale_specific'][$currentLocaleCode][$this->code] = $value;

            return;
        }

        $productValues['common'][$this->code] = $value;
    }

    /**
     * Attribute type validations for value formats and options existance
     */
    public function fieldTypeValidations(): array
    {
        $rules = [];

        switch ($this->type) {
            case self::BOOLEAN_FIELD_TYPE:
                $rules[] = new BooleanString;

                break;
            case self::DATETIME_FIELD_TYPE:
                $rules[] = 'date_format:Y-m-d H:i:s';

                break;
            case self::DATE_FIELD_TYPE:
                $rules[] = 'date';
                $rules[] = 'date_format:Y-m-d';

                break;
            case self::SELECT_FIELD_TYPE:
            case self::MULTISELECT_FIELD_TYPE:
                $rules[] = 'string';
                $rules[] = new AttributeOptionRule($this);

                break;
            case self::CHECKBOX_FIELD_TYPE:
                $rules[] = new AttributeOptionRule($this);

                break;
            case AttributeTypes::FILE_ATTRIBUTE_TYPE:
                $rules[] = new FileOrImageValidValue;

                break;
            case AttributeTypes::IMAGE_ATTRIBUTE_TYPE:
            case AttributeTypes::GALLERY_ATTRIBUTE_TYPE:
                $rules[] = new FileOrImageValidValue(isImage: true, isMultiple: $this->type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE);

                break;
        }

        return $rules;
    }

    /**
     * Get attribute filter type
     */
    public function getFilterType()
    {
        switch ($this->type) {
            case self::BOOLEAN_FIELD_TYPE:
                $filterType = 'boolean';
                break;
            case self::DATETIME_FIELD_TYPE:
                $filterType = 'datetime_range';
                break;
            case self::DATE_FIELD_TYPE:
                $filterType = 'date_range';
                break;
            case self::SELECT_FIELD_TYPE:
            case self::MULTISELECT_FIELD_TYPE:
            case self::CHECKBOX_FIELD_TYPE:
                $filterType = 'dropdown';
                break;
            case self::IMAGE_ATTRIBUTE_TYPE:
            case self::GALLERY_ATTRIBUTE_TYPE:
                $filterType = 'image';
                break;
            case self::PRICE_FIELD_TYPE:
                $filterType = 'price';
                break;
            default:
                $filterType = 'string';
                break;
        }

        return $filterType;
    }

    /**
     * Get Attribute  scope
     */
    public function getScope(?string $locale = null, ?string $channel = null): string
    {
        return ($this->value_per_locale && $this->value_per_channel)
        ? sprintf('channel_locale_specific.%s.%s', $channel, $locale)
        : ($this->value_per_locale
            ? sprintf('locale_specific.%s', $locale)
            : ($this->value_per_channel
                ? sprintf('channel_specific.%s', $channel)
                : 'common'));
    }

    /**
     * Get the options by option code and locale.
     */
    public function getOptionsByCodeAndLocale($codes, $locale = null)
    {
        $locale = $locale ?? core()->getRequestedLocaleCode();

        return $this->options()
            ->leftJoin('attribute_option_translations as aot', 'aot.attribute_option_id', 'attribute_options.id')
            ->whereIn('attribute_options.code', $codes)
            ->where(function ($query) use ($locale) {
                $query->where('aot.locale', $locale)
                    ->orWhereNull('aot.locale'); // Fallback if translation not found
            })
            ->select('attribute_options.*', 'aot.label')
            ->get();
    }
}
