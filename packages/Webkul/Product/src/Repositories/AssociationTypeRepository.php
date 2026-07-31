<?php

namespace Webkul\Product\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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

        return DB::transaction(function () use ($data, $id, $fields) {
            $associationType = parent::update($data, $id);

            /**
             * The browser keeps a field flagged as new until the page reloads, so a
             * resubmitted form (a failed first attempt, a double submit) would insert
             * a code that already exists. Resolving the row by its code instead of
             * trusting the flag keeps the save idempotent.
             */
            $existingIds = $this->associationTypeFieldRepository->getModel()->newQuery()
                ->where('association_type_id', $associationType->id)
                ->pluck('id', 'code');

            foreach ($fields as $fieldId => $fieldData) {
                $isNew = filter_var($fieldData['isNew'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $resolvedId = $isNew
                    ? ($existingIds[$fieldData['code'] ?? ''] ?? null)
                    : $fieldId;

                if ($resolvedId !== null && ! $existingIds->contains($resolvedId)) {
                    $resolvedId = $existingIds[$fieldData['code'] ?? ''] ?? null;
                }

                if (filter_var($fieldData['isDelete'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    if ($resolvedId) {
                        $this->associationTypeFieldRepository->delete($resolvedId);
                    }

                    continue;
                }

                if (! $resolvedId) {
                    $this->associationTypeFieldRepository->create(array_merge($fieldData, [
                        'association_type_id' => $associationType->id,
                    ]));

                    continue;
                }

                $this->associationTypeFieldRepository->update($fieldData, $resolvedId);
            }

            return $associationType;
        });
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

    /**
     * Active association types for the given ids, with the same translation/
     * field/option graph as getActiveTypes() but bounded to the supplied set,
     * so the product edit page loads only the types a product actually links to
     * rather than every active type (which does not scale as types grow).
     */
    public function getActiveTypesByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return new Collection;
        }

        return $this->where(['status' => 1])
            ->whereIn('id', $ids)
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
