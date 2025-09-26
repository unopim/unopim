<?php

namespace Webkul\Completeness\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Core\Models\Channel;

class CompletenessSettingFactory extends Factory
{
    protected $model = CompletenessSetting::class;

    public function definition(): array
    {
        return [
            'family_id'    => AttributeFamily::factory(),
            'attribute_id' => Attribute::factory(),
            'channel_id'   => Channel::first(),
        ];
    }
}
