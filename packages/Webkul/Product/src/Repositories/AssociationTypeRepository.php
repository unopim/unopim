<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\AssociationType;

class AssociationTypeRepository extends Repository
{
    /**
     * Create a new association type repository instance
     */
    public function __construct(Container $container, protected AssociationTypeFieldRepository $associationTypeFieldRepository)
    {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return AssociationType::class;
    }

    /**
     * Create a new association type along with its translations and fields.
     *
     * @return AssociationType
     */
    public function create(array $data)
    {
        $fields = $data['fields'] ?? [];

        unset($data['fields']);

        $associationType = parent::create($data);

        foreach ($fields as $field) {
            $this->associationTypeFieldRepository->create(array_merge($field, [
                'association_type_id' => $associationType->id,
            ]));
        }

        return $associationType;
    }

    /**
     * Update an association type along with its translations and fields.
     *
     * @param  int  $id
     * @return AssociationType
     */
    public function update(array $data, $id)
    {
        $fields = $data['fields'] ?? [];

        unset($data['fields']);

        $associationType = parent::update($data, $id);

        foreach ($fields as $fieldId => $fieldData) {
            if ($fieldData['isNew'] == 'true') {
                $this->associationTypeFieldRepository->create(array_merge($fieldData, [
                    'association_type_id' => $associationType->id,
                ]));

                continue;
            }

            if ($fieldData['isDelete'] == 'true') {
                $this->associationTypeFieldRepository->delete($fieldId);
            } else {
                $this->associationTypeFieldRepository->update($fieldData, $fieldId);
            }
        }

        return $associationType;
    }

    /**
     * Retrieves active association types with translations, fields (with their
     * translations), and field options (with their translations) eager loaded,
     * so consumers (e.g. the product edit page) can resolve translated
     * names/labels for every type/field/option without triggering N+1 queries.
     */
    public function getActiveTypes(): Collection
    {
        return $this->where(['status' => 1])
            ->with(['translations', 'fields.translations', 'fields.options.translations'])
            ->orderBy('position')
            ->get();
    }

    public function queryBuilder()
    {
        return $this->with(['translations']);
    }

    /**
     * Find an association type by its unique code.
     */
    public function findByCode(string $code): ?AssociationType
    {
        return $this->findOneByField('code', $code);
    }
}
