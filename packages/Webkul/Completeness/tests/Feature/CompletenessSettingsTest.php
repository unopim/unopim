<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;
use Webkul\Core\Models\Channel;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return completeness datagrid json for a family', function () {
    $family = AttributeFamily::factory()->create();

    $response = $this->getJson(route('admin.catalog.families.completeness.edit', $family->id));

    $response->assertOk()
        ->assertJsonStructure([
            'records',
            'columns',
        ]);
});

it('should insert and delete completeness settings on update', function () {
    Queue::fake();

    $family = AttributeFamily::factory()->create();
    $attribute = Attribute::factory()->create();

    $channel1 = Channel::factory()->create(['code' => 'ch1']);
    $channel2 = Channel::factory()->create(['code' => 'ch2']);

    app(CompletenessSettingsRepository::class)->create([
        'family_id'    => $family->id,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel2->id,
    ]);

    $payload = [
        'familyId'            => $family->id,
        'attributeId'         => $attribute->id,
        'channel_requirements'=> 'ch1',
    ];

    $this->postJson(route('admin.catalog.families.completeness.update'), $payload)
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('completeness::app.catalog.families.edit.completeness.update-success'),
        ]);

    $this->assertDatabaseHas('completeness_settings', [
        'family_id'    => $family->id,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel1->id,
    ]);

    $this->assertDatabaseMissing('completeness_settings', [
        'family_id'    => $family->id,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel2->id,
    ]);

    Queue::assertPushedOn('system', BulkProductCompletenessJob::class, function ($job) use ($family) {
        return $job->uniqueId() === 'completeness-job-'.$family->id;
    });
});

it('should apply channel requirements across multiple attributes on mass update', function () {
    Queue::fake();

    $family = AttributeFamily::factory()->create();
    $attribute1 = Attribute::factory()->create();
    $attribute2 = Attribute::factory()->create();

    $channel = Channel::factory()->create(['code' => 'mass_ch']);

    $payload = [
        'familyId'            => $family->id,
        'indices'             => [$attribute1->id, $attribute2->id],
        'channel_requirements'=> 'mass_ch',
    ];

    $this->postJson(route('admin.catalog.families.completeness.mass_update'), $payload)
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('completeness::app.catalog.families.edit.completeness.mass-update-success'),
        ]);

    foreach ([$attribute1->id, $attribute2->id] as $attrId) {
        $this->assertDatabaseHas('completeness_settings', [
            'family_id'    => $family->id,
            'attribute_id' => $attrId,
            'channel_id'   => $channel->id,
        ]);
    }

    Queue::assertPushedOn('system', BulkProductCompletenessJob::class, function ($job) use ($family) {
        return $job->uniqueId() === 'completeness-job-'.$family->id;
    });
});

it('should not dispatch job if mass update does not change any channel requirements', function () {
    Queue::fake();

    $family = AttributeFamily::factory()->create();
    $attribute1 = Attribute::factory()->create();
    $attribute2 = Attribute::factory()->create();

    $channel = Channel::factory()->create(['code' => 'unchanged_mass']);

    foreach ([$attribute1, $attribute2] as $attr) {
        app(CompletenessSettingsRepository::class)->create([
            'family_id'    => $family->id,
            'attribute_id' => $attr->id,
            'channel_id'   => $channel->id,
        ]);
    }

    $payload = [
        'familyId'            => $family->id,
        'indices'             => [$attribute1->id, $attribute2->id],
        'channel_requirements'=> 'unchanged_mass', // same as existing
    ];

    $this->postJson(route('admin.catalog.families.completeness.mass_update'), $payload)
        ->assertOk();

    Queue::assertNotPushed(BulkProductCompletenessJob::class);
});

it('should dispatch job when a completeness setting is deleted via update', function () {
    Queue::fake();

    $family = AttributeFamily::factory()->create();
    $attribute = Attribute::factory()->create();

    $channel1 = Channel::factory()->create(['code' => 'keep']);
    $channel2 = Channel::factory()->create(['code' => 'delete_me']);

    $repo = app(CompletenessSettingsRepository::class);

    $repo->create([
        'family_id'    => $family->id,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel1->id,
    ]);

    $repo->create([
        'family_id'    => $family->id,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel2->id,
    ]);

    $payload = [
        'familyId'            => $family->id,
        'attributeId'         => $attribute->id,
        'channel_requirements'=> 'keep',
    ];

    $this->postJson(route('admin.catalog.families.completeness.update'), $payload)
        ->assertOk();

    Queue::assertPushedOn('system', BulkProductCompletenessJob::class, function ($job) use ($family) {
        return $job->uniqueId() === 'completeness-job-'.$family->id;
    });
});

it('should dispatch job when completeness settings are deleted via mass update', function () {
    Queue::fake();

    $family = AttributeFamily::factory()->create();
    $attribute1 = Attribute::factory()->create();
    $attribute2 = Attribute::factory()->create();

    $channelKeep = Channel::factory()->create(['code' => 'keep']);
    $channelDelete = Channel::factory()->create(['code' => 'delete_me']);

    $repo = app(CompletenessSettingsRepository::class);

    foreach ([$attribute1, $attribute2] as $attr) {
        $repo->create([
            'family_id'    => $family->id,
            'attribute_id' => $attr->id,
            'channel_id'   => $channelKeep->id,
        ]);
        $repo->create([
            'family_id'    => $family->id,
            'attribute_id' => $attr->id,
            'channel_id'   => $channelDelete->id,
        ]);
    }

    $payload = [
        'familyId'            => $family->id,
        'indices'             => [$attribute1->id, $attribute2->id],
        'channel_requirements'=> 'keep',
    ];

    $this->postJson(route('admin.catalog.families.completeness.mass_update'), $payload)
        ->assertOk();

    foreach ([$attribute1->id, $attribute2->id] as $attrId) {
        $this->assertDatabaseMissing('completeness_settings', [
            'family_id'    => $family->id,
            'attribute_id' => $attrId,
            'channel_id'   => $channelDelete->id,
        ]);
        $this->assertDatabaseHas('completeness_settings', [
            'family_id'    => $family->id,
            'attribute_id' => $attrId,
            'channel_id'   => $channelKeep->id,
        ]);
    }

    Queue::assertPushedOn('system', BulkProductCompletenessJob::class, function ($job) use ($family) {
        return $job->uniqueId() === 'completeness-job-'.$family->id;
    });
});
