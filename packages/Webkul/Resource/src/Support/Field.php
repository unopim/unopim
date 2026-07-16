<?php

namespace Webkul\Resource\Support;

class Field
{
    protected string $type = 'text';

    protected ?string $label = null;

    protected string $rules = '';

    protected array $options = [];

    protected mixed $default = null;

    protected bool $translatable = false;

    protected bool $required = false;

    public function __construct(protected string $name) {}

    /**
     * Make a text input field.
     */
    public static function text(string $name): self
    {
        return (new self($name))->setType('text');
    }

    /**
     * Make a select field.
     */
    public static function select(string $name): self
    {
        return (new self($name))->setType('select');
    }

    /**
     * Make a textarea field.
     */
    public static function textarea(string $name): self
    {
        return (new self($name))->setType('textarea');
    }

    protected function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Mark the field as required (for display purposes; enforce via rules()).
     */
    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Set the Laravel validation rules string for the field.
     */
    public function rules(string $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Set the selectable options for a select field.
     *
     * @param  array<int, array{id: mixed, label: string}>  $options
     */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set the default value used when no record/old input value is present.
     */
    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Set the display label; defaults to the field name when unset.
     */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Mark the field as translatable.
     */
    public function translatable(bool $translatable = true): self
    {
        $this->translatable = $translatable;

        return $this;
    }

    /**
     * Get the field's underlying name/attribute key.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the field's raw validation rules string.
     */
    public function getRules(): string
    {
        return $this->rules;
    }

    /**
     * Convert the field to its view-model array representation.
     *
     * @return array{name: string, type: string, label: string, rules: string, options: array, default: mixed, translatable: bool, required: bool}
     */
    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'type'         => $this->type,
            'label'        => $this->label ?? $this->name,
            'rules'        => $this->rules,
            'options'      => $this->options,
            'default'      => $this->default,
            'translatable' => $this->translatable,
            'required'     => $this->required,
        ];
    }
}
