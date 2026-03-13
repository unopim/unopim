<?php

namespace Webkul\Measurement\Repository;

use Webkul\Core\Eloquent\Repository;

class AttributeMeasurementRepository extends Repository
{
    public function model()
    {
        return 'Webkul\\Measurement\\Models\\AttributeMeasurement';
    }

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

    public function getByAttributeId($attributeId)
    {
        return $this->findOneWhere(['attribute_id' => $attributeId]);
    }

    public function saveAttributeMeasurementByCode($attributeCode, $data)
    {
        $attribute = app(\Webkul\Attribute\Repositories\AttributeRepository::class)
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
