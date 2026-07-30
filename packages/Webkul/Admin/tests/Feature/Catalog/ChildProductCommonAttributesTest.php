<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(fn () => ensureDefaultChannelCarriesBothLocales());

function ensureDefaultChannelCarriesBothLocales(): void
{
    Locale::whereIn('code', ['de_DE', 'en_US'])->update(['status' => 1]);

    $channel = core()->getDefaultChannel();

    $channel->locales()->syncWithoutDetaching(
        Locale::whereIn('code', ['de_DE', 'en_US'])->pluck('id')->all()
    );

    $channel->unsetRelation('locales');
}

function authorInLocale(string $code): void
{
    auth()->guard('admin')->user()->forceFill([
        'catalog_locale_id' => Locale::where('code', $code)->value('id'),
    ])->save();
}

function issue1202Fixture(): array
{
    $axisCode = 'axis_'.Str::random(8);
    $commonCode = 'cmn_'.Str::random(8);
    $localisedCode = 'loc_'.Str::random(8);

    $axis = Attribute::factory()->create(['code' => $axisCode, 'type' => 'select']);

    Attribute::factory()->create([
        'code'              => $commonCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    Attribute::factory()->create([
        'code'              => $localisedCode,
        'type'              => 'text',
        'value_per_locale'  => 1,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $axisCode,
        $commonCode,
        $localisedCode,
    ])->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $axis->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'CFG-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$axisCode],
    ]);

    $configurable->values = [
        'common' => [
            'sku'       => $configurable->sku,
            $commonCode => 'PARENT-COMMON-VALUE',
        ],
        'locale_specific' => [
            'en_US' => [$localisedCode => 'PARENT-EN-US-VALUE'],
        ],
    ];

    $configurable->save();

    $child = $configurable->getTypeInstance()->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $configurable->id,
        'sku'       => $configurable->sku.'-v1',
        'values'    => ['common' => [$axisCode => $axis->options->first()->code]],
    ]);

    return [
        'configurable'  => $configurable->fresh(),
        'child'         => Product::find($child->id),
        'axisCode'      => $axisCode,
        'commonCode'    => $commonCode,
        'localisedCode' => $localisedCode,
    ];
}

function invokeVariantFieldLocks(Product $product): ?array
{
    $controller = app(ProductController::class);

    $method = new ReflectionMethod($controller, 'buildVariantFieldLocks');
    $method->setAccessible(true);

    return $method->invoke($controller, $product);
}

function renderedLocaleOf(string $content): ?string
{
    preg_match('/name="locale" value="([^"]*)"/', $content, $matches);

    return $matches[1] ?? null;
}

function renderedLocaleBucketFor(string $content, string $attributeCode): ?string
{
    preg_match('/values\[locale_specific\]\[([a-zA-Z_]+)\]\['.preg_quote($attributeCode, '/').'\]/', $content, $matches);

    return $matches[1] ?? null;
}

function rendersControlValue(string $content, string $value): bool
{
    return str_contains($content, 'value="'.$value.'"');
}

describe('storage and ancestor resolution', function () {
    it('never writes the inherited values onto the child row', function () {
        $this->loginAsAdmin();

        $fixture = issue1202Fixture();

        $parentValues = json_decode(DB::table('products')->where('id', $fixture['configurable']->id)->value('values'), true);
        $childValues = json_decode(DB::table('products')->where('id', $fixture['child']->id)->value('values'), true);

        expect($parentValues['common'][$fixture['commonCode']])->toBe('PARENT-COMMON-VALUE')
            ->and($parentValues['locale_specific']['en_US'][$fixture['localisedCode']])->toBe('PARENT-EN-US-VALUE')
            ->and($childValues['common'])->not->toHaveKey($fixture['commonCode'])
            ->and($childValues)->not->toHaveKey('locale_specific');
    });

    it('resolves both inherited values through the ancestor chain on the child', function () {
        $this->loginAsAdmin();

        $fixture = issue1202Fixture();

        $resolved = $fixture['child']->resolvedValues();

        expect($resolved['common'][$fixture['commonCode']])->toBe('PARENT-COMMON-VALUE')
            ->and($resolved['locale_specific']['en_US'][$fixture['localisedCode']])->toBe('PARENT-EN-US-VALUE');
    });
});

describe('catalog locale diverging from the locale the page renders in', function () {
    it('reads the inherited locks under the catalog locale the fields are rendered in', function () {
        $this->loginAsAdmin();

        authorInLocale('de_DE');

        $fixture = issue1202Fixture();

        $locks = invokeVariantFieldLocks($fixture['child']);

        expect(core()->getRequestedLocaleCode())->toBe('de_DE')
            ->and(core()->getRequestedLocale()?->code)->toBe('de_DE')
            ->and($locks['locks'][$fixture['commonCode']]['value'])->toBe('PARENT-COMMON-VALUE')
            ->and($locks['locks'][$fixture['localisedCode']]['value'])->toBeNull();
    });

    it('drops the inherited locale scoped field on the child edit page but keeps the common scoped one', function () {
        $this->loginAsAdmin();

        authorInLocale('de_DE');

        $fixture = issue1202Fixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect(renderedLocaleOf($content))->toBe('de_DE')
            ->and(renderedLocaleBucketFor($content, $fixture['localisedCode']))->toBe('de_DE')
            ->and(rendersControlValue($content, 'PARENT-COMMON-VALUE'))->toBeTrue()
            ->and(rendersControlValue($content, 'PARENT-EN-US-VALUE'))->toBeFalse();
    });

    it('still renders the parent its own locale scoped value under the same divergence', function () {
        $this->loginAsAdmin();

        authorInLocale('de_DE');

        $fixture = issue1202Fixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['configurable']->id))
            ->assertOk()
            ->getContent();

        expect(rendersControlValue($content, 'PARENT-COMMON-VALUE'))->toBeTrue()
            ->and(rendersControlValue($content, 'PARENT-EN-US-VALUE'))->toBeFalse();
    });

    it('renders every inherited value once the catalog locale matches the authoring locale', function () {
        $this->loginAsAdmin();

        authorInLocale('en_US');

        $fixture = issue1202Fixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect(rendersControlValue($content, 'PARENT-COMMON-VALUE'))->toBeTrue()
            ->and(rendersControlValue($content, 'PARENT-EN-US-VALUE'))->toBeTrue();
    });
});

describe('no catalog locale set, the trigger the issue was reported under', function () {
    it('documents how the catalog locale resolves against the locale the page renders in', function () {
        $admin = $this->loginAsAdmin();

        expect($admin->catalog_locale_id)->toBeNull();

        $channel = core()->getDefaultChannel();

        expect($channel->locales()->pluck('code')->all())->toContain('en_US')
            ->and($channel->locales()->pluck('code')->all())->toContain('de_DE');
    });

    it('renders every inherited value when the catalog locale resolves to the configured application locale', function () {
        $this->loginAsAdmin();

        $fixture = issue1202Fixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        expect(core()->getRequestedLocaleCode())->toBe(core()->getRequestedLocale()?->code)
            ->and(rendersControlValue($content, 'PARENT-COMMON-VALUE'))->toBeTrue()
            ->and(rendersControlValue($content, 'PARENT-EN-US-VALUE'))->toBeTrue();
    });

    it('renders the same locale the scope resolves, whatever order the channel lists them in', function () {
        $this->loginAsAdmin();

        $channel = core()->getDefaultChannel();

        $unorderedFirst = DB::table('channel_locales')
            ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
            ->where('channel_locales.channel_id', $channel->id)
            ->value('locales.code');

        expect($unorderedFirst)->toBe('de_DE')
            ->and(core()->getRequestedLocale()?->code)->toBe(core()->getRequestedLocaleCode());
    });
});
