<?php

namespace Webkul\Measurement\Repositories;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Measurement\Contracts\AttributeMeasurement;

class AttributeMeasurementRepository extends Repository
{
    /**
     * Get model class name.
     *
     * @return string
     */
    public function model()
    {
        return AttributeMeasurement::class;
    }

    /**
     * Create or update attribute measurement configuration.
     *
     * @param  int|string  $attributeId
     * @param  array  $data
     * @return mixed
     */
    public function saveAttributeMeasurement($attributeId, $data)
    {
        return $this->updateOrCreate(
            ['attribute_id' => $attributeId],
            [
                'family_code' => $data['family_code'] ?? null,
                'unit_code'   => $data['unit_code'] ?? null,
            ]
        );
    }

    /**
     * Get measurement configuration by attribute id.
     *
     * @param  int|string  $attributeId
     * @return mixed
     */
    public function getByAttributeId($attributeId)
    {
        return $this->findOneWhere(['attribute_id' => $attributeId]);
    }

    /**
     * Whether any attribute is configured against the given measurement family.
     */
    public function isFamilyInUse(string $familyCode): bool
    {
        return $this->findWhere(['family_code' => $familyCode])->isNotEmpty();
    }

    /**
     * Whether any attribute is configured against the given unit.
     */
    public function isUnitInUse(string $unitCode): bool
    {
        return $this->findWhere(['unit_code' => $unitCode])->isNotEmpty();
    }

    /**
     * Create or update attribute measurement using attribute code.
     *
     * @param  string  $attributeCode
     * @param  array  $data
     */
    public function saveAttributeMeasurementByCode($attributeCode, $data): void
    {
        $attribute = resolve(AttributeRepository::class)
            ->findOneByField('code', $attributeCode);

        if ($attribute) {
            $this->updateOrCreate(
                ['attribute_id' => $attribute->id],
                [
                    'family_code' => $data['value'] ?? null,
                    'unit_code'   => $data['unit'] ?? null,
                ]
            );
        }
    }
}
