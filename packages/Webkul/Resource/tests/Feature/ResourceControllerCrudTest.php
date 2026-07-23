<?php

use Webkul\Resource\Tests\Fixtures\TestRepository;

it('shows create and edit forms', function () {
    $this->loginAsAdmin();
    $item = app(TestRepository::class)->create(['name' => 'Edit Me']);

    $this->get(route('admin.resource-kit-items.create'))->assertOk()->assertViewIs('resource::edit');
    $this->get(route('admin.resource-kit-items.edit', $item->id))->assertOk()->assertViewIs('resource::edit');
});

it('updates and deletes a record', function () {
    $this->loginAsAdmin();
    $item = app(TestRepository::class)->create(['name' => 'Old']);

    $this->putJson(route('admin.resource-kit-items.update', $item->id), ['name' => 'New'])
        ->assertOk()->assertJsonStructure(['data' => ['redirect_url'], 'message']);
    $this->assertDatabaseHas('wk_resource_kit_items', ['id' => $item->id, 'name' => 'New']);

    $this->deleteJson(route('admin.resource-kit-items.destroy', $item->id))
        ->assertOk()->assertJsonStructure(['message']);
    $this->assertDatabaseMissing('wk_resource_kit_items', ['id' => $item->id]);
});
