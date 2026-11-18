<?php

it('returns 404 instead of 500 for a history suffix on system settings pages', function () {
    $this->loginAsAdmin();

    foreach (['system.appearance', 'system.email', 'system.measurement'] as $section) {
        $this->get('/'.config('app.admin_url')."/configuration/system/{$section}/history")
            ->assertNotFound();
    }
});
