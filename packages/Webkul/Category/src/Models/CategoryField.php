<?php

namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Category\Contracts\CategoryField as CategoryFieldContract;
use Webkul\Category\Database\Factories\CategoryFieldFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Rules\Decimal;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;

class CategoryField extends TranslatableModel implements CategoryFieldContract, HistoryContract
{
    use HasFactory;
    use HistoryTrait;

    const NON_DELETABLE_FIELD_CODE = 'name';

    /** Tags for History */
    protected $historyTags = ['category_field'];

    /** Fields for History */
    protected $historyFields = [
        'root_category_id',
    ];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [
        'options',
    ];

    /**
     * Translated attributes.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name',
    ];

    /**
     * Fillable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'enable_wysiwyg',
        'position',
        'status',
        'section',
        'is_required',
        'is_unique',
        'validation',
        'value_per_locale',
        'regex_pattern',
    ];

    /**
     * field types.
     *
     * @var array
     */
    public $categoryTypeFields = [
        'text'        => 'text_value',
        'textarea'    => 'text_value',
        'boolean'     => 'boolean_value',
        'select'      => 'integer_value',
        'multiselect' => 'text_value',
        'datetime'    => 'datetime_value',
        'date'        => 'date_value',
        'file'        => 'text_value',
        'image'       => 'text_value',
        'checkbox'    => 'text_value',
    ];

    /**
     * Get all of the options for the CategoryField
     */
    public function options(): HasMany
    {
        return $this->hasMany(CategoryFieldOptionProxy::modelClass());
    }

    /**
     * Returns category field value table column based type
     */
    protected function getColumnNameField(): string
    {
        return $this->categoryTypeFields[$this->type];
    }

    /**
     * Returns field validation rules
     */
    public function getValidationsField(): string
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required: true';
        }

        if ($this->type === 'file') {
            $validations[] = 'size:'.(core()->getConfigData('catalog.categories.fields.file_attribute_upload_size') ?? '2048');
        }

        if ($this->type === 'image') {
            $retVal = core()->getConfigData('catalog.categories.fields.image_attribute_upload_size') ?? '2048';

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
     * Returns field validation rules for API and internal functions
     */
    public function getValidationsFieldWithOutMedia(): array
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required';
        }

        if ($this->validation) {
            $validations[] = match ($this->validation) {
                'regex'   => 'regex: "'.$this->regex_pattern.'"',
                'number'  => 'numeric',
                'decimal' => new Decimal(),
                default   => $this->validation
            };
        }

        return $validations;
    }

    /**
     * Returns the validation rule for unique field based on the configuration.
     *
     * @return string|null The validation rule string or null if no validation is required.
     */
    public function getValidationUniqueField()
    {
        $validation = null;

        if ($this->value_per_locale && $this->is_unique) {
            $validation = 'unique:categories,additional_data->locale_specific->%s->%s';
        }

        if (! $this->value_per_locale && $this->is_unique) {
            $validation = 'unique:categories,additional_data->common->%s';
        }

        return $validation;
    }

    /**
     * Returns field validation rules for API and internal functions
     */
    public function getValidationsFieldOnlyMedia(): array
    {
        $validations = [];

        if ($this->is_required) {
            $validations[] = 'required';
        }

        if ($this->type === 'file') {
            $validations[] = 'file';
            $validations[] = 'max:'.(core()->getConfigData('catalog.categories.fields.file_attribute_upload_size') ?? '5120');
        }

        if ($this->type === 'image') {
            $validations[] = 'file';
            $validations[] = 'mimes:bmp,jpeg,jpg,png';
            $retVal = core()->getConfigData('catalog.categories.fields.image_attribute_upload_size') ?? '5120';

            if ($retVal) {
                $validations[] = 'max:'.$retVal.'';
            }
        }

        return $validations;
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return CategoryFieldFactory::new();
    }

    /**
     * check if possible to delete this attribute
     */
    public function canBeDeleted()
    {
        return $this->code !== self::NON_DELETABLE_FIELD_CODE;
    }

    /**
     * Checks if this field is based on locale
     */
    public function isLocaleBasedField(): bool
    {
        return (bool) $this->value_per_locale;
    }

    /**
     * Validation rules for validator
     * used while validating category field values
     */
    public function getValidationRules(?string $currentLocaleCode = null, ?int $id = null, bool $withUniqueValidation = true)
    {
        $validations = $this->is_required ? ['required'] : ['nullable'];

        if ($this->is_unique && $withUniqueValidation) {
            $path = $this->getJsonPath($currentLocaleCode);

            $rule = "unique:categories,additional_data->{$path}";

            if ($id) {
                $rule .= ",{$id}";
            }

            $validations[] = $rule;
        }

        if ($this->validation) {
            $validations[] = match ($this->validation) {
                'regex'   => 'regex: "'.$this->regex_pattern.'"',
                'number'  => 'numeric',
                'decimal' => new Decimal(),
                default   => $this->validation
            };
        }

        return $validations;
    }

    /**
     * Get path used to access value in json column of sql
     */
    public function getJsonPath(?string $currentLocaleCode): string
    {
        if ($this->isLocaleBasedField()) {
            return 'locale_specific->'.$currentLocaleCode.'->'.$this->code;
        }

        return 'common->'.$this->code;
    }
}
