<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

use function Pest\Laravel\get;

/**
 * Builds two channels with disjoint locale/currency scopes so the
 * channel-driven filtering can be asserted deterministically.
 */
function seedScopeChannels(): array
{
    $enUS = Locale::updateOrCreate(['code' => 'en_US'], ['status' => 1]);
    $frFR = Locale::updateOrCreate(['code' => 'fr_FR'], ['status' => 1]);
    $deDE = Locale::updateOrCreate(['code' => 'de_DE'], ['status' => 1]);

    $usd = Currency::updateOrCreate(['code' => 'USD'], ['status' => 1]);
    $eur = Currency::updateOrCreate(['code' => 'EUR'], ['status' => 1]);
    $gbp = Currency::updateOrCreate(['code' => 'GBP'], ['status' => 1]);

    $web = Channel::factory()->create(['code' => 'web']);
    $web->locales()->sync([$enUS->id, $frFR->id]);
    $web->currencies()->sync([$usd->id, $eur->id]);

    $mobile = Channel::factory()->create(['code' => 'mobile']);
    $mobile->locales()->sync([$deDE->id]);
    $mobile->currencies()->sync([$gbp->id]);

    // The prettus repository cache survives the test transaction; flush it so
    // the controller reads the freshly seeded channels.
    Cache::flush();

    return compact('web', 'mobile');
}

function optionCodes($response): array
{
    return collect($response->json('options'))->pluck('code')->all();
}

beforeEach(function () {
    $this->loginAsAdmin();
});

describe('Export filter channel options', function () {
    it('lists the available channels', function () {
        seedScopeChannels();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.channels')));

        expect($codes)->toContain('web')
            ->toContain('mobile');
    });
});

describe('Export filter locale options', function () {
    it('returns only the locales of the selected channel', function () {
        seedScopeChannels();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.locales', ['channels' => ['web']])));

        expect($codes)->toContain('en_US')
            ->toContain('fr_FR')
            ->not->toContain('de_DE');
    });

    it('falls back to every active locale when no channel is selected', function () {
        seedScopeChannels();

        // de_DE belongs only to the mobile channel, so it is reachable only via the fallback.
        $scoped = optionCodes(get(route('admin.settings.data_transfer.exports.filters.locales', [
            'channels' => ['web'],
            'query'    => 'de_DE',
        ])));

        $fallback = optionCodes(get(route('admin.settings.data_transfer.exports.filters.locales', [
            'query' => 'de_DE',
        ])));

        expect($scoped)->not->toContain('de_DE');
        expect($fallback)->toContain('de_DE');
    });

    it('drops rehydrated locales that are invalid for the selected channel', function () {
        seedScopeChannels();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.locales', [
            'channels'    => ['web'],
            'identifiers' => ['columnName' => 'code', 'values' => ['en_US', 'de_DE']],
        ])));

        expect($codes)->toContain('en_US')
            ->not->toContain('de_DE');
    });
});

describe('Export filter attribute options', function () {
    it('returns matching attributes for a search query', function () {
        Attribute::factory()->create(['code' => 'export_scope_attr']);

        Cache::flush();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.attributes', [
            'query' => 'export_scope_attr',
        ])));

        expect($codes)->toContain('export_scope_attr');
    });

    it('includes the attribute id and type so the value input can adapt', function () {
        $attribute = Attribute::factory()->create(['code' => 'export_typed_attr', 'type' => 'select']);

        Cache::flush();

        $response = get(route('admin.settings.data_transfer.exports.filters.attributes', [
            'query' => 'export_typed_attr',
        ]));

        $option = collect($response->json('options'))->firstWhere('code', 'export_typed_attr');

        expect($option)->not->toBeNull();
        expect($option['id'])->toBe($attribute->id);
        expect($option['type'])->toBe('select');
    });

    it('drops attribute codes passed via the exclude param', function () {
        Attribute::factory()->create(['code' => 'export_excluded_attr']);

        Cache::flush();

        $without = optionCodes(get(route('admin.settings.data_transfer.exports.filters.attributes', [
            'query' => 'export_excluded_attr',
        ])));

        $with = optionCodes(get(route('admin.settings.data_transfer.exports.filters.attributes', [
            'query'   => 'export_excluded_attr',
            'exclude' => ['export_excluded_attr'],
        ])));

        expect($without)->toContain('export_excluded_attr');
        expect($with)->not->toContain('export_excluded_attr');
    });
});

describe('Export filter attribute family options', function () {
    it('returns matching attribute families for a search query', function () {
        AttributeFamily::factory()->create(['code' => 'export_scope_family']);

        Cache::flush();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.attribute_families', [
            'query' => 'export_scope_family',
        ])));

        expect($codes)->toContain('export_scope_family');
    });
});

describe('Export filter category options', function () {
    it('returns matching categories for a search query', function () {
        Category::factory()->create(['code' => 'export_scope_category']);

        Cache::flush();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.categories', [
            'query' => 'export_scope_category',
        ])));

        expect($codes)->toContain('export_scope_category');
    });
});

describe('Export filter currency options', function () {
    it('returns only the currencies of the selected channel', function () {
        seedScopeChannels();

        $codes = optionCodes(get(route('admin.settings.data_transfer.exports.filters.currencies', ['channels' => ['web']])));

        expect($codes)->toContain('USD')
            ->toContain('EUR')
            ->not->toContain('GBP');
    });

    it('falls back to every active currency when no channel is selected', function () {
        seedScopeChannels();

        $scoped = optionCodes(get(route('admin.settings.data_transfer.exports.filters.currencies', [
            'channels' => ['web'],
            'query'    => 'GBP',
        ])));

        $fallback = optionCodes(get(route('admin.settings.data_transfer.exports.filters.currencies', [
            'query' => 'GBP',
        ])));

        expect($scoped)->not->toContain('GBP');
        expect($fallback)->toContain('GBP');
    });
});
