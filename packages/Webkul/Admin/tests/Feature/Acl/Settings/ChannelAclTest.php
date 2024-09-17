<?php

use Webkul\Core\Models\Channel;

it('should not display the channel list if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.settings.channels.index'));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the channel list if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.channels']);

    $this->get(route('admin.settings.channels.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.channels.index.title'));
});

it('should not display the create channel form if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $response = $this->get(route('admin.settings.channels.create'));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the create channel form if have permission', function () {
    $this->loginWithPermissions('custom', ['settings', 'settings.channels', 'settings.channels.create']);

    $response = $this->get(route('admin.settings.channels.create'));
    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.channels.index.title'));
});

it('should not display the channel edit if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $channel = Channel::first();

    $response = $this->get(route('admin.settings.channels.edit', ['id' => $channel->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());
});

it('should display the channel edit if have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings.channels.edit']);
    $channel = Channel::first();

    $response = $this->get(route('admin.settings.channels.edit', ['id' => $channel->id]));
    $response->assertStatus(200)
        ->assertSeeTextInOrder([
            trans('admin::app.settings.channels.edit.title'),
        ]);
});

it('should not be able to delete channel if does not have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);
    $channel = Channel::first();

    $response = $this->delete(route('admin.settings.channels.delete', ['id' => $channel->id]));
    $this->assertStringContainsString('Unauthorized', $response->getContent());

    $this->assertDatabaseHas($this->getFullTableName(channel::class),
        ['id' => $channel->id]
    );
});

it('should be able to delete channel if have permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings.channels.delete']);

    $demoChannel = Channel::factory()->create();

    $this->delete(route('admin.settings.channels.delete', ['id' => $demoChannel->id]));

    $this->assertDatabaseMissing($this->getFullTableName(Channel::class), [
        'id' => $demoChannel->id,
    ]);
});
