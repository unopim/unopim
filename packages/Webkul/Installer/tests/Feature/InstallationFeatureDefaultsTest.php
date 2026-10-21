<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Database\Seeders\AgenticPimConfigSeeder;
use Webkul\Core\Models\Channel;
use Webkul\Installer\Database\Seeders\Demo\DemoOrganisationSeeder;
use Webkul\ProductPassport\Services\PassportFeature;

it('leaves DPP and AI chat disabled when installing without demo data', function (): void {
    DB::table('core_config')->whereIn('code', [
        'catalog.product_passport.settings.enabled',
        'general.magic_ai.agentic_pim.enabled',
        'general.magic_ai.agentic_pim.open_by_default',
    ])->delete();

    $this->seed(AgenticPimConfigSeeder::class);

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeFalse()
        ->and(resolve(PassportFeature::class)->enabledFor(Channel::query()->firstOrFail()))->toBeFalse();

    foreach (['enabled', 'open_by_default'] as $setting) {
        $this->assertDatabaseHas('core_config', [
            'code'         => 'general.magic_ai.agentic_pim.'.$setting,
            'value'        => '0',
            'channel_code' => null,
            'locale_code'  => null,
        ]);
    }
});

it('preserves saved AI chat settings when installation defaults are seeded again', function (string $value): void {
    foreach (['enabled', 'open_by_default'] as $setting) {
        DB::table('core_config')->updateOrInsert([
            'code'         => 'general.magic_ai.agentic_pim.'.$setting,
            'channel_code' => null,
            'locale_code'  => null,
        ], ['value' => $value]);
    }

    $this->seed(AgenticPimConfigSeeder::class);

    foreach (['enabled', 'open_by_default'] as $setting) {
        $this->assertDatabaseHas('core_config', [
            'code'         => 'general.magic_ai.agentic_pim.'.$setting,
            'value'        => $value,
            'channel_code' => null,
            'locale_code'  => null,
        ]);
    }
})->with(['enabled' => '1', 'disabled' => '0']);

it('enables DPP and AI chat when demo settings are installed', function (): void {
    Storage::fake('public');

    DB::table('core_config')->whereIn('code', [
        'catalog.product_passport.settings.enabled',
        'general.magic_ai.agentic_pim.enabled',
        'general.magic_ai.agentic_pim.open_by_default',
    ])->delete();

    $this->seed(AgenticPimConfigSeeder::class);
    $this->seed(DemoOrganisationSeeder::class);

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeTrue();

    foreach (['enabled', 'open_by_default'] as $setting) {
        $this->assertDatabaseHas('core_config', [
            'code'         => 'general.magic_ai.agentic_pim.'.$setting,
            'value'        => '1',
            'channel_code' => null,
            'locale_code'  => null,
        ]);
    }
});
