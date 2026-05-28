<?php

it('should not display webhook logs if user does not have permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'configuration.webhook.settings']);

    $this->get(route('webhook.logs.index'))
        ->assertStatus(403);
});

it('should display webhook logs if user has permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'configuration.webhook', 'configuration.webhook.logs']);

    $this->get(route('webhook.logs.index'))
        ->assertStatus(200);
});

it('should hide logs tab link when user lacks logs permission', function () {
    $viewPath = base_path('packages/Webkul/Webhook/src/Resources/views/settings/index.blade.php');
    $content = file_get_contents($viewPath);

    // The Logs tab link must be wrapped in a bouncer permission check
    expect($content)->toContain("bouncer()->hasPermission('configuration.webhook.logs')");
});

it('should hide logs tab content when user lacks logs permission', function () {
    $viewPath = base_path('packages/Webkul/Webhook/src/Resources/views/settings/index.blade.php');
    $content = file_get_contents($viewPath);

    // The Logs tab content inclusion must also be permission-gated
    // Both the tab link and content block should check for configuration.webhook.logs
    $logsPermissionCount = substr_count($content, "bouncer()->hasPermission('configuration.webhook.logs')");

    expect($logsPermissionCount)->toBeGreaterThanOrEqual(2);
});

it('webhook logs controller should check permission in index method', function () {
    $controllerPath = base_path('packages/Webkul/Webhook/src/Http/Controllers/WebhookLogsController.php');
    $content = file_get_contents($controllerPath);

    expect($content)->toContain("bouncer()->hasPermission('configuration.webhook.logs')");
});
