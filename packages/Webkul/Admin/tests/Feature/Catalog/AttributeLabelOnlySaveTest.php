<?php

use Webkul\Attribute\Models\Attribute;

use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('leaves the attribute row untouched when only the label changed', function () {
    $attribute = Attribute::factory()->create(['is_required' => 0, 'is_filterable' => 0]);

    $touchedAt = $attribute->updated_at;

    $this->travel(2)->seconds();

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'          => $attribute->code,
        'type'          => $attribute->type,
        'is_required'   => 0,
        'is_filterable' => 0,
        'en_US'         => ['name' => 'Renamed label'],
    ])->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    expect($attribute->fresh()->updated_at->timestamp)->toBe($touchedAt->timestamp);
});

it('still writes flags that actually changed', function () {
    $attribute = Attribute::factory()->create(['is_required' => 0]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'is_required' => 1,
    ])->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    expect((int) $attribute->fresh()->is_required)->toBe(1);
});
