<?php

namespace Webkul\Resource\Support;

class FieldSchema
{
    /** @param  Field[]  $fields */
    public function __construct(protected array $fields = []) {}

    /**
     * Build a schema from a list of fields.
     *
     * @param  Field[]  $fields
     */
    public static function make(array $fields): self
    {
        return new self(array_values($fields));
    }

    /**
     * Get the underlying fields.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * Convert all fields to their view-model array representation.
     */
    public function toArray(): array
    {
        return array_map(fn (Field $field): array => $field->toArray(), $this->fields);
    }

    /**
     * Build a Laravel validation rules array keyed by field name, skipping fields without rules.
     */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            if ($field->getRules() !== '') {
                $rules[$field->getName()] = $field->getRules();
            }
        }

        return $rules;
    }

    /**
     * Return a new schema containing only the fields matching the callback.
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->fields, $callback)));
    }
}
