<?php

namespace Webkul\Product\Tests\Concerns;

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

trait InteractsWithTemplates
{
    protected function templateAttribute(string $code, array $overrides = []): Attribute
    {
        return $this->variableAttribute($code, 'textarea', ['enable_templates' => 1, ...$overrides]);
    }

    protected function variableAttribute(string $code, string $type = 'text', array $overrides = []): Attribute
    {
        return Attribute::factory()->create([
            'code'       => $code,
            'type'       => $type,
            'validation' => null,
            ...$overrides,
        ]);
    }

    protected function attributeOption(Attribute $attribute, string $code, array $labels = []): AttributeOption
    {
        $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'code' => $code]);

        foreach ($labels as $locale => $label) {
            $option->translateOrNew($locale)->label = $label;
        }

        $option->save();

        return $option;
    }

    protected function templateChannel(array $currencies = ['EUR', 'USD'], array $locales = ['en_US', 'de_DE']): Channel
    {
        $channel = Channel::factory()->create(['code' => 'tpl_'.Str::lower(Str::random(8))]);

        $channel->currencies()->sync(array_map(
            fn (string $code): int => Currency::query()->firstOrCreate(['code' => $code], ['symbol' => $code, 'decimal' => 2, 'status' => 1])->id,
            $currencies,
        ));

        $channel->locales()->sync(array_map(
            fn (string $code): int => Locale::query()->firstOrCreate(['code' => $code], ['status' => 1])->id,
            $locales,
        ));

        return $channel->refresh();
    }
}
