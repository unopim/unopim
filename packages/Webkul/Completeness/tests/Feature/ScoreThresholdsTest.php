<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Completeness\Enums\ScoreLevel;
use Webkul\Completeness\Services\ScoreThresholds;
use Webkul\Core\Repositories\CoreConfigRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

function storeScoreThresholds(mixed $greenFrom, mixed $redBelow): void
{
    app(CoreConfigRepository::class)->create([
        'system' => ['completeness' => [
            'green_from' => $greenFrom,
            'red_below'  => $redBelow,
        ]],
    ]);
}

it('returns the default thresholds when none are configured', function () {
    expect(app(ScoreThresholds::class)->toArray())->toBe([
        'greenFrom' => 80.0,
        'redBelow'  => 50.0,
    ]);
});

it('returns the configured thresholds', function () {
    storeScoreThresholds('90', '60');

    $thresholds = app(ScoreThresholds::class);

    expect($thresholds->greenFrom())->toBe(90.0)
        ->and($thresholds->redBelow())->toBe(60.0);
});

it('falls back to the defaults when the stored thresholds are no longer ordered', function () {
    storeScoreThresholds('40', '60');

    expect(app(ScoreThresholds::class)->toArray())->toBe([
        'greenFrom' => 80.0,
        'redBelow'  => 50.0,
    ]);
});

it('maps a score to its colour level using the configured thresholds', function (float $score, ScoreLevel $level) {
    storeScoreThresholds('90', '60');

    expect(app(ScoreThresholds::class)->level($score))->toBe($level);
})->with([
    'at green'     => [90, ScoreLevel::Success],
    'above green'  => [100, ScoreLevel::Success],
    'below green'  => [89.9, ScoreLevel::Warning],
    'at red'       => [60, ScoreLevel::Warning],
    'below red'    => [59.9, ScoreLevel::Danger],
    'zero'         => [0, ScoreLevel::Danger],
]);

it('lists the score thresholds section in the system settings hub', function () {
    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(trans('completeness::app.configuration.score-thresholds.title'), false);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.completeness']))
        ->assertOk()
        ->assertSee(trans('completeness::app.configuration.score-thresholds.green-from'), false)
        ->assertSee(trans('completeness::app.configuration.score-thresholds.red-below'), false);
});

it('persists valid thresholds saved from the system settings hub', function () {
    $this->put(route('admin.settings.system.update', ['key' => 'system.completeness']), [
        'system' => ['completeness' => [
            'green_from' => '90',
            'red_below'  => '60',
        ]],
    ])
        ->assertSessionDoesntHaveErrors()
        ->assertRedirect(route('admin.settings.system.edit', ['key' => 'system.completeness']));

    $this->assertDatabaseHas('core_config', ['code' => ScoreThresholds::GREEN_FROM_CODE, 'value' => '90']);
    $this->assertDatabaseHas('core_config', ['code' => ScoreThresholds::RED_BELOW_CODE, 'value' => '60']);
});

it('rejects a green threshold that is not above the red threshold', function (string $greenFrom, string $redBelow) {
    $this->put(route('admin.settings.system.update', ['key' => 'system.completeness']), [
        'system' => ['completeness' => [
            'green_from' => $greenFrom,
            'red_below'  => $redBelow,
        ]],
    ])->assertSessionHasErrors([
        ScoreThresholds::GREEN_FROM_CODE => trans('completeness::app.configuration.score-thresholds.order-error'),
    ]);

    $this->assertDatabaseMissing('core_config', ['code' => ScoreThresholds::GREEN_FROM_CODE]);
    $this->assertDatabaseMissing('core_config', ['code' => ScoreThresholds::RED_BELOW_CODE]);
})->with([
    'equal'    => ['60', '60'],
    'inverted' => ['50', '70'],
]);

it('rejects thresholds that are missing or outside 0 to 100', function (array $payload, string $field) {
    $this->put(route('admin.settings.system.update', ['key' => 'system.completeness']), [
        'system' => ['completeness' => $payload],
    ])->assertSessionHasErrors("system.completeness.$field");

    $this->assertDatabaseMissing('core_config', ['code' => "system.completeness.$field"]);
})->with([
    'green above 100'   => [['green_from' => '101', 'red_below' => '50'], 'green_from'],
    'red below zero'    => [['green_from' => '80', 'red_below' => '-1'], 'red_below'],
    'green not numeric' => [['green_from' => 'abc', 'red_below' => '50'], 'green_from'],
    'red missing'       => [['green_from' => '80'], 'red_below'],
]);

it('rejects a file posted at a threshold field alongside its text value and stores nothing', function () {
    Storage::fake();

    $request = Request::create(
        route('admin.settings.system.update', ['key' => 'system.completeness']),
        'PUT',
        ['system' => ['completeness' => ['green_from' => '90', 'red_below' => '60']]],
        [],
        ['system' => ['completeness' => ['green_from' => UploadedFile::fake()->create('payload.php', 1)]]],
        ['HTTP_ACCEPT' => 'application/json'],
    );

    TestResponse::fromBaseResponse(app(Kernel::class)->handle($request))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('system.completeness.green_from');

    $this->assertDatabaseMissing('core_config', ['code' => ScoreThresholds::GREEN_FROM_CODE]);
    $this->assertDatabaseMissing('core_config', ['code' => ScoreThresholds::RED_BELOW_CODE]);
    expect(Storage::allFiles('configuration'))->toBeEmpty();
});

it('returns the ordering error as json for an ajax save', function () {
    $this->putJson(route('admin.settings.system.update', ['key' => 'system.completeness']), [
        'system' => ['completeness' => [
            'green_from' => '40',
            'red_below'  => '60',
        ]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([ScoreThresholds::GREEN_FROM_CODE]);
});

it('gates the score thresholds section by its own permission', function () {
    $this->loginWithPermissions(permissions: ['configuration.system_settings']);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.completeness']))
        ->assertForbidden();

    $this->put(route('admin.settings.system.update', ['key' => 'system.completeness']), [
        'system' => ['completeness' => ['green_from' => '90', 'red_below' => '60']],
    ])->assertForbidden();

    $this->assertDatabaseMissing('core_config', ['code' => ScoreThresholds::GREEN_FROM_CODE]);

    $this->loginWithPermissions(permissions: [
        'configuration.system_settings',
        'configuration.system_settings.completeness',
    ]);

    $this->get(route('admin.settings.system.edit', ['key' => 'system.completeness']))
        ->assertOk();
});

it('passes the configured thresholds to the dashboard completeness indicators', function () {
    storeScoreThresholds('90', '60');

    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('$completeness', false)
        ->assertSee('"greenFrom":90', false)
        ->assertSee('"redBelow":60', false);
});

it('passes the default thresholds to the dashboard when none are configured', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee('"greenFrom":80', false)
        ->assertSee('"redBelow":50', false);
});

it('colours the product grid completeness pill with the configured thresholds', function (float $score, string $pill) {
    storeScoreThresholds('90', '60');

    $closure = app(ProductDataGrid::class)->getPropertyColumns()['completeness']['closure'];

    expect($closure((object) ['completeness' => $score]))->toContain($pill);
})->with([
    'green' => [92, 'pill-high'],
    'amber' => [85, 'pill-medium'],
    'red'   => [55, 'pill-low'],
]);
