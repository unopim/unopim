<?php

it('should not display the webhook logs tab if user does not have logs permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.settings']);

    $this->get(route('webhook.settings.index', ['logs' => true]))
        ->assertOk()
        ->assertDontSeeText(trans('webhook::app.configuration.webhook.settings.index.logs-title'));
});

it('should display the webhook logs tab if user has logs permission', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.settings', 'configuration.webhook.logs']);

    $this->get(route('webhook.settings.index'))
        ->assertOk()
        ->assertSeeText(trans('webhook::app.configuration.webhook.settings.index.logs-title'));
});

it('should not render webhook logs content when user lacks logs permission even if logs query param is present', function () {
    $this->loginWithPermissions(permissions: ['configuration', 'configuration.webhook', 'configuration.webhook.settings']);

    $response = $this->get(route('webhook.settings.index', ['logs' => true]));

    $response->assertOk();
    $response->assertDontSeeText(trans('webhook::app.configuration.webhook.logs.index.title'));
});
