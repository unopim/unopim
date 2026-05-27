<?php

it('should hide the Add Platform button when user lacks ai-agent.platform.create permission (Issue #719)', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform']);

    $response = $this->get(route('admin.magic_ai.platform.index'));

    $response->assertOk();
    $response->assertDontSeeText(trans('admin::app.configuration.platform.create-btn'));
});

it('should show the Add Platform button when user has ai-agent.platform.create permission', function () {
    $this->loginWithPermissions('custom', ['ai-agent', 'ai-agent.platform', 'ai-agent.platform.create']);

    $response = $this->get(route('admin.magic_ai.platform.index'));

    $response->assertOk();
    $response->assertSeeText(trans('admin::app.configuration.platform.create-btn'));
});
