<?php

it('uses an icon from the admin icon font for every system settings item', function (): void {
    $css = file_get_contents(base_path('packages/Webkul/Admin/src/Resources/assets/css/app.css'));

    $missing = collect(config('system_settings'))
        ->pluck('icon')
        ->filter()
        ->reject(fn (string $icon): bool => str_contains($css, ".{$icon}:before"))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});
