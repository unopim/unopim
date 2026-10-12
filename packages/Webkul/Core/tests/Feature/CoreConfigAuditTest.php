<?php

use Webkul\Core\Models\CoreConfig;

it('records an audit trail when a core config value changes', function () {
    $config = CoreConfig::create(['code' => 'general.test.audit_field', 'value' => 'first']);

    $config->update(['value' => 'second']);

    $audit = $config->audits()->where('event', 'updated')->orderByDesc('id')->first();

    expect($config->audits()->count())->toBeGreaterThanOrEqual(1)
        ->and($audit->old_values['value'] ?? null)->toBe('first')
        ->and($audit->new_values['value'] ?? null)->toBe('second');
});

it('redacts a password-type config value in the audit trail', function () {
    // This code resolves to a `password` field in config('core'), so the value
    // must never appear in the audit.
    $config = CoreConfig::create([
        'code'  => 'emails.configure.email_settings.mail_password',
        'value' => 'super-secret-old',
    ]);

    $config->update(['value' => 'super-secret-new']);

    $audit = $config->audits()->where('event', 'updated')->orderByDesc('id')->first();

    expect($audit->new_values['value'] ?? null)->not->toContain('super-secret')
        ->and($audit->old_values['value'] ?? null)->not->toContain('super-secret');
});
