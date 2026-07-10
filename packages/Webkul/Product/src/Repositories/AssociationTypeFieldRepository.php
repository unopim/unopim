<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\AssociationTypeField;

class AssociationTypeFieldRepository extends Repository
{
    protected array $fieldWithOptions = [
        'select',
        'multiselect',
        'checkbox',
    ];

    /**
     * Create a new association type field repository instance
     */
    public function __construct(Container $container, protected AssociationTypeFieldOptionRepository $associationTypeFieldOptionRepository)
    {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AssociationTypeField::class;
    }

    /**
     * Create a new association type field according to type and options
     *
     * @return AssociationTypeField
     */
    public function create(array $data)
    {
        $associationTypeField = parent::create($data);

        if (
            ! in_array($associationTypeField->type, $this->fieldWithOptions)
            || ! isset($data['options'])
        ) {
            return $associationTypeField;
        }

        foreach ($data['options'] as $option) {
            $this->associationTypeFieldOptionRepository->create(array_merge($option, [
                'association_type_field_id' => $associationTypeField->id,
            ]));
        }

        return $associationTypeField;
    }

    /**
     * Update an association type field in the database and its options if present.
     *
     * @param  int  $id
     * @return AssociationTypeField
     */
    public function update(array $data, $id)
    {
        $associationTypeField = parent::update($data, $id);

        if (
            ! in_array($associationTypeField->type, $this->fieldWithOptions)
            || ! isset($data['options'])
        ) {
            return $associationTypeField;
        }

        foreach ($data['options'] as $optionId => $option) {
            if ($option['isNew'] == 'true') {
                $this->associationTypeFieldOptionRepository->create(array_merge([
                    'association_type_field_id' => $associationTypeField->id,
                ], $option));

                continue;
            }

            if ($option['isDelete'] == 'true') {
                $this->associationTypeFieldOptionRepository->delete($optionId);
            } else {
                $this->associationTypeFieldOptionRepository->update($option, $optionId);
            }
        }

        return $associationTypeField;
    }

    /**
     * Retrieves active association type fields by section
     */
    public function getActiveFieldsBySection(string $section = 'left'): Collection
    {
        return $this->where(['status' => 1, 'section' => $section])->with(['translations', 'options', 'options.translations'])->orderBy('position')->get();
    }

    /**
     * Retrieves active association type fields
     */
    public function getActiveFields(): Collection
    {
        return $this->where(['status' => 1])->orderBy('position')->get();
    }

    public function queryBuilder()
    {
        return $this->with(['translations']);
    }
}
