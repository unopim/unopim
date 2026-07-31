<?php

it('renders the reset password form with a single password ref so confirmation validates', function () {
    $response = $this->get(route('admin.reset_password.create', 'sample-token'));

    $response->assertStatus(200);

    $content = $response->getContent();

    expect(substr_count($content, 'ref="password"'))->toBe(1)
        ->and($content)->toContain('confirmed:@password');
});
