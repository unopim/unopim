<?php

namespace Webkul\Measurement\Repository;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\Repository;

class AttributeMeasurementRepository extends Repository
{
    /**
     * Get model class name.
     *
     * @return string
     */
    public function model()
    {
        return 'Webkul\\Measurement\\Models\\AttributeMeasurement';
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
     * Create or update attribute measurement using attribute code.
     *
     * @param  string  $attributeCode
     * @param  array  $data
     * @return void
     */
    public function saveAttributeMeasurementByCode($attributeCode, $data)
    {
        $attribute = app(AttributeRepository::class)
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
