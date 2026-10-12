<?php

use Webkul\User\Models\Admin;

it('should not render the AI agent chat widget on anonymous / error pages', function () {
    config(['general.magic_ai.agentic_pim.enabled' => 1]);

    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $html = view('admin::errors.index', ['errorCode' => 403])->render();

    expect($html)->not->toContain('v-agenting-pim-template');
});
