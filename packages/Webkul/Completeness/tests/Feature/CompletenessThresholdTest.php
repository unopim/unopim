<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Webkul\Completeness\Helpers\Threshold;
use Webkul\Completeness\Listeners\ValidateThresholdSettings;
use Webkul\Core\Models\CoreConfig;

beforeEach(function (): void {
    $this->loginAsAdmin();

    CoreConfig::whereIn('code', [
        'system.completeness.green_from',
        'system.completeness.red_below',
    ])->delete();

    Cache::flush();
});

/**
 * Persist a completeness threshold config value. Pass null to leave it unset
 * so the helper's own default applies.
 */
function setCompletenessThreshold(?string $greenFrom, ?string $redBelow): void
{
    foreach (['green_from' => $greenFrom, 'red_below' => $redBelow] as $field => $value) {
        if ($value === null) {
            continue;
        }

        CoreConfig::updateOrCreate(
            ['code' => "system.completeness.{$field}"],
            ['value' => $value]
        );
    }

    Cache::flush();
}

it('returns the shipped defaults when neither threshold is configured', function (): void {
    expect(app(Threshold::class)->bounds())->toBe([
        Threshold::DEFAULT_GREEN_FROM,
        Threshold::DEFAULT_RED_BELOW,
    ])->and(app(Threshold::class)->bounds())->toBe([90, 60]);
});

it('reads the configured thresholds when the green bound is above the red bound', function (): void {
    setCompletenessThreshold('95', '40');

    expect(app(Threshold::class)->bounds())->toBe([95, 40]);
});

it('falls back to the defaults when the green bound is not greater than the red bound', function (): void {
    setCompletenessThreshold('50', '60');

    expect(app(Threshold::class)->bounds())->toBe([90, 60]);
});

it('falls back to the defaults when the two thresholds are equal', function (): void {
    setCompletenessThreshold('70', '70');

    expect(app(Threshold::class)->bounds())->toBe([90, 60]);
});

it('clamps a configured threshold into the 0..100 range', function (): void {
    setCompletenessThreshold('150', '40');

    expect(app(Threshold::class)->bounds())->toBe([100, 40]);
});

it('ignores a non-numeric configured threshold and uses the default for that bound', function (): void {
    setCompletenessThreshold('not-a-number', '40');

    // green_from is non-numeric so it defaults to 90; 90 > 40 holds, so the pair stands.
    expect(app(Threshold::class)->bounds())->toBe([90, 40]);
});

it('registers the completeness threshold settings with 90/60 defaults', function (): void {
    $section = collect(config('system_settings'))->firstWhere('key', 'system.completeness');

    expect($section)->not->toBeNull();

    $fields = collect($section['fields']);

    $green = $fields->firstWhere('name', 'green_from');
    $red = $fields->firstWhere('name', 'red_below');

    expect($green['type'])->toBe('number')
        ->and($green['default_value'])->toBe('90')
        ->and($red['type'])->toBe('number')
        ->and($red['default_value'])->toBe('60');
});

it('registers the completeness configuration acl entry', function (): void {
    $keys = collect(config('acl'))->pluck('key');

    expect($keys)->toContain('configuration.system_settings.completeness');
});

it('passes validation for a green bound greater than the red bound', function (): void {
    request()->merge([
        'system' => ['completeness' => ['green_from' => '90', 'red_below' => '60']],
    ]);

    expect(fn () => app(ValidateThresholdSettings::class)->handle())
        ->not->toThrow(ValidationException::class);
});

it('does nothing when neither threshold is present in the request', function (): void {
    expect(fn () => app(ValidateThresholdSettings::class)->handle())
        ->not->toThrow(ValidationException::class);
});

it('rejects a green bound that is not greater than the red bound', function (): void {
    request()->merge([
        'system' => ['completeness' => ['green_from' => '50', 'red_below' => '60']],
    ]);

    expect(fn () => app(ValidateThresholdSettings::class)->handle())
        ->toThrow(ValidationException::class);
});

it('rejects a threshold outside the 0..100 range', function (): void {
    request()->merge([
        'system' => ['completeness' => ['green_from' => '120', 'red_below' => '60']],
    ]);

    expect(fn () => app(ValidateThresholdSettings::class)->handle())
        ->toThrow(ValidationException::class);
});

it('rejects a missing red bound when the green bound is submitted', function (): void {
    request()->merge([
        'system' => ['completeness' => ['green_from' => '80']],
    ]);

    expect(fn () => app(ValidateThresholdSettings::class)->handle())
        ->toThrow(ValidationException::class);
});

it('surfaces threshold field errors when the settings page is posted', function (): void {
    $this->post(route('admin.configuration.store', ['slug' => 'system', 'slug2' => 'completeness']), [
        'system' => ['completeness' => ['green_from' => '50', 'red_below' => '60']],
    ])->assertSessionHasErrors('system.completeness.green_from');

    expect(session('error'))->toBeNull();
});
