<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

class AttributeFamilyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttributeFamily::class;

    /**
     * Define the model's default state.
     *
     * @throws \Exception
     */
    public function definition(): array
    {
        return [
            'name'   => $this->faker->word(),
            'code'   => $this->faker->word(),
            'status' => 0,
        ];
    }

    /**
     * Add a new attribute group to the family
     */
    public function linkAttributeGroupToFamily(AttributeFamily $family, ?AttributeGroup $attributeGroup = null): AttributeFamily
    {
        $family->familyGroups()->attach($attributeGroup ?? AttributeGroup::factory()->create());

        return $family;
    }

    /**
     * link the attribute to an attribute group already linked to a family
     */
    public function linkAttributesToFamily(AttributeFamily $family, mixed $attributes = null): AttributeFamily
    {
        $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attributes ?? Attribute::factory()->create());

        return $family;
    }

    /**
     * Link required attributes status and sku to the family
     */
    public function linkStatusAndSkuToFamily(AttributeFamily $family): AttributeFamily
    {
        $attributes = Attribute::whereIn('code', ['sku', 'status'])->get();

        $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attributes);

        return $family;
    }

    /**
     * Add required attribute to family
     */
    public function withRequiredAttributes(?Attribute $attributes = null): AttributeFamilyFactory
    {
        return $this->afterCreating(function (AttributeFamily $family) {
            $this->linkAttributeGroupToFamily($family);

            $this->linkAttributesToFamily($family, $attributes ?? Attribute::factory()->create(['is_required' => 1]));

            $this->linkStatusAndSkuToFamily($family);
        });
    }

    /**
     * Add required attribute to family
     */
    public function withMinimalAttributesForProductTypes(?Attribute $attributes = null): AttributeFamilyFactory
    {
        return $this->afterCreating(function (AttributeFamily $family) {
            $this->linkAttributeGroupToFamily($family);

            $this->linkAttributesToFamily($family, $attributes ?? Attribute::factory()->create(['type' => 'select']));

            $this->linkStatusAndSkuToFamily($family);
        });
    }
}
