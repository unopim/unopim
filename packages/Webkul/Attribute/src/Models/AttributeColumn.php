<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Contracts\AttributeColumn as AttributeColumnContract;
use Webkul\Attribute\Database\Factories\AttributeColumnFactory;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\Core\Rules\BooleanString;
use Webkul\Core\Rules\FileOrImageValidValue;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Validator\Rule\AttributeColumnOptionRule;

class AttributeColumn extends TranslatableModel implements AttributeColumnContract, HistoryContract
{
    use HasFactory;
    use HistoryTrait;

    public $timestamps = false;

    public $translatedAttributes = ['label'];

    protected $historyTags = ['attribute'];

    protected $fillable = [
        'code',
        'type',
        'validation',
        'attribute_id',
        'sort_order',
        'extra',
    ];

    const BOOLEAN_FIELD_TYPE = 'boolean';

    const SELECT_FIELD_TYPE = 'select';

    const MULTISELECT_FIELD_TYPE = 'multiselect';

    const DATETIME_FIELD_TYPE = 'datetime';

    const DATE_FIELD_TYPE = 'date';

    /**
     * Get the attribute that owns the attribute option.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(AttributeProxy::modelClass());
    }

    public function options()
    {
        return $this->hasMany(AttributeColumnOption::class);
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeColumnFactory::new();
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->attribute_id;
    }

    public function getValidationRules()
    {
        $rules = $this->fieldTypeValidations();

        if ($this->validation) {
            $rules[] = $this->validation;
        }

        return $rules;
    }

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
                $rules[] = new AttributeColumnOptionRule($this);

                break;
            case AttributeTypes::FILE_ATTRIBUTE_TYPE:
                $rules[] = new FileOrImageValidValue;

                break;
            case AttributeTypes::IMAGE_ATTRIBUTE_TYPE:
                $rules[] = new FileOrImageValidValue(isImage: true, isMultiple: $this->type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE);
                break;
        }

        return $rules;
    }
}
