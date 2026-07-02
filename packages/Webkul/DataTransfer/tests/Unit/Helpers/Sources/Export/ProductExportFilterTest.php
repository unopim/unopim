<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Sources\Export\Filters\ProductExportFilter;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

function makeProduct(string $sku, int $familyId, array $values, int $status = 1): Product
{
    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => $status,
        'attribute_family_id' => $familyId,
    ]);

    $product->values = $values;
    $product->save();

    return $product;
}

function filteredSkus(array $filters): array
{
    $query = app(ProductRepository::class)->getModel()->newQuery();

    app(ProductExportFilter::class)->applyToQuery($query, $filters);

    return $query->pluck('sku')->sort()->values()->all();
}

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);
});

describe('Product export structural filters', function () {
    beforeEach(function () {
        $this->familyA = AttributeFamily::factory()->create(['code' => 'fam_a']);
        $this->familyB = AttributeFamily::factory()->create(['code' => 'fam_b']);

        $this->p1 = makeProduct('SKU-1', $this->familyA->id, ['common' => ['sku' => 'SKU-1', 'color' => 'black'], 'categories' => ['cat_x']]);
        $this->p2 = makeProduct('SKU-2', $this->familyB->id, ['common' => ['sku' => 'SKU-2', 'color' => 'white'], 'categories' => ['cat_y']]);
        $this->p3 = makeProduct('SKU-3', $this->familyA->id, ['common' => ['sku' => 'SKU-3', 'color' => 'black'], 'categories' => ['cat_x', 'cat_y']]);

        Product::where('id', $this->p1->id)->update(['updated_at' => now()->subDay()]);
        Product::where('id', $this->p2->id)->update(['updated_at' => now()->subDays(30)]);
        Product::where('id', $this->p3->id)->update(['updated_at' => now()->subDay()]);

        Cache::flush();
    });

    it('filters by attribute family', function () {
        expect(filteredSkus(['attribute_families' => ['fam_a']]))->toContain('SKU-1', 'SKU-3')->not->toContain('SKU-2');
    });

    it('filters by category', function () {
        expect(filteredSkus(['categories' => ['cat_y']]))->toContain('SKU-2', 'SKU-3')->not->toContain('SKU-1');
    });

    it('filters by a custom sku attribute value', function () {
        expect(filteredSkus(['custom_attributes' => [['attribute' => 'sku', 'value' => 'SKU-1']]]))
            ->toContain('SKU-1')->not->toContain('SKU-2', 'SKU-3');
    });

    it('filters by a custom common attribute value', function () {
        expect(filteredSkus(['custom_attributes' => [['attribute' => 'color', 'value' => 'black']]]))
            ->toContain('SKU-1', 'SKU-3')->not->toContain('SKU-2');
    });

    it('combines custom attribute filters with AND semantics', function () {
        $filters = ['custom_attributes' => [
            ['attribute' => 'color', 'value' => 'black'],
            ['attribute' => 'sku', 'value' => 'SKU-3'],
        ]];

        expect(filteredSkus($filters))->toContain('SKU-3')->not->toContain('SKU-1', 'SKU-2');
    });

    it('filters by updated_after date', function () {
        expect(filteredSkus(['updated_after' => now()->subDays(7)->toDateTimeString()]))
            ->toContain('SKU-1', 'SKU-3')->not->toContain('SKU-2');
    });

    it('filters by updated_before date', function () {
        expect(filteredSkus(['updated_before' => now()->subDays(7)->toDateTimeString()]))
            ->toContain('SKU-2')->not->toContain('SKU-1', 'SKU-3');
    });

    it('filters between two updated_at bounds', function () {
        $filters = [
            'updated_after'  => now()->subDays(40)->toDateTimeString(),
            'updated_before' => now()->subDays(7)->toDateTimeString(),
        ];

        expect(filteredSkus($filters))->toContain('SKU-2')->not->toContain('SKU-1', 'SKU-3');
    });

    it('filters by a comma separated sku list', function () {
        expect(filteredSkus(['sku' => 'SKU-1, SKU-3']))
            ->toContain('SKU-1', 'SKU-3')->not->toContain('SKU-2');
    });

    it('filters by a space separated sku list', function () {
        expect(filteredSkus(['sku' => 'SKU-1 SKU-3']))
            ->toContain('SKU-1', 'SKU-3')->not->toContain('SKU-2');
    });

    it('filters by a mixed comma and space separated sku list', function () {
        expect(filteredSkus(['sku' => "SKU-1,  SKU-2\nSKU-3"]))
            ->toContain('SKU-1', 'SKU-2', 'SKU-3');
    });

    it('ignores empty entries in the sku list', function () {
        expect(filteredSkus(['sku' => ' SKU-2 ,, ']))
            ->toContain('SKU-2')->not->toContain('SKU-1', 'SKU-3');
    });

    it('returns every product when no filter is set', function () {
        expect(filteredSkus([]))->toContain('SKU-1', 'SKU-2', 'SKU-3');
    });
});

describe('Product export status filter', function () {
    beforeEach(function () {
        $this->family = AttributeFamily::factory()->create(['code' => 'fam_status']);

        makeProduct('STA-ON', $this->family->id, ['common' => ['sku' => 'STA-ON']], 1);
        makeProduct('STA-OFF', $this->family->id, ['common' => ['sku' => 'STA-OFF']], 0);

        Cache::flush();
    });

    it('keeps only enabled products for the enable status', function () {
        expect(filteredSkus(['status' => 'enable']))->toContain('STA-ON')->not->toContain('STA-OFF');
    });

    it('keeps only disabled products for the disable status', function () {
        expect(filteredSkus(['status' => 'disable']))->toContain('STA-OFF')->not->toContain('STA-ON');
    });

    it('keeps every product for the all status', function () {
        expect(filteredSkus(['status' => 'all']))->toContain('STA-ON', 'STA-OFF');
    });
});

describe('Product export multi-value custom attribute filter', function () {
    beforeEach(function () {
        $this->family = AttributeFamily::factory()->create(['code' => 'fam_multi']);

        Attribute::factory()->create(['code' => 'colors_multi', 'type' => 'multiselect']);

        $this->matching = makeProduct('MUL-1', $this->family->id, ['common' => ['sku' => 'MUL-1', 'colors_multi' => 'red,blue']]);
        $this->substring = makeProduct('MUL-2', $this->family->id, ['common' => ['sku' => 'MUL-2', 'colors_multi' => 'darkred']]);
        $this->other = makeProduct('MUL-3', $this->family->id, ['common' => ['sku' => 'MUL-3', 'colors_multi' => 'green']]);

        Cache::flush();
    });

    it('matches a single option code inside a comma separated multiselect value', function () {
        $skus = filteredSkus(['custom_attributes' => [['attribute' => 'colors_multi', 'value' => ['red']]]]);

        expect($skus)->toContain('MUL-1')->not->toContain('MUL-2', 'MUL-3');
    });

    it('matches any of the selected option codes', function () {
        $skus = filteredSkus(['custom_attributes' => [['attribute' => 'colors_multi', 'value' => ['red', 'green']]]]);

        expect($skus)->toContain('MUL-1', 'MUL-3')->not->toContain('MUL-2');
    });
});

describe('Product export completeness filter', function () {
    beforeEach(function () {
        $this->family = AttributeFamily::factory()->create(['code' => 'fam_c']);
        $locale = Locale::updateOrCreate(['code' => 'en_US'], ['status' => 1]);
        $channel = Channel::factory()->create(['code' => 'web']);
        $channel->locales()->sync([$locale->id]);

        $this->complete = makeProduct('COMP-1', $this->family->id, ['common' => ['sku' => 'COMP-1']]);
        $this->incomplete = makeProduct('COMP-2', $this->family->id, ['common' => ['sku' => 'COMP-2']]);

        ProductCompletenessScore::create([
            'product_id'    => $this->complete->id,
            'channel_id'    => $channel->id,
            'locale_id'     => $locale->id,
            'score'         => 100,
            'missing_count' => 0,
        ]);

        ProductCompletenessScore::create([
            'product_id'    => $this->incomplete->id,
            'channel_id'    => $channel->id,
            'locale_id'     => $locale->id,
            'score'         => 40,
            'missing_count' => 5,
        ]);

        $this->scope = ['channels' => ['web'], 'locales' => ['en_US']];

        Cache::flush();
    });

    it('matches products complete on at least one selected locale', function () {
        $skus = filteredSkus(array_merge($this->scope, ['completeness' => 'at_least_one']));

        expect($skus)->toContain('COMP-1')->not->toContain('COMP-2');
    });

    it('matches products complete on all selected locales', function () {
        $skus = filteredSkus(array_merge($this->scope, ['completeness' => 'all']));

        expect($skus)->toContain('COMP-1')->not->toContain('COMP-2');
    });

    it('does not filter when completeness condition is none', function () {
        $skus = filteredSkus(array_merge($this->scope, ['completeness' => 'none']));

        expect($skus)->toContain('COMP-1', 'COMP-2');
    });
});

describe('Product export attribute condition operators', function () {
    beforeEach(function () {
        $this->family = AttributeFamily::factory()->create(['code' => 'fam_ops']);

        Attribute::factory()->create(['code' => 'unit_price', 'type' => 'price']);
        Attribute::factory()->create(['code' => 'release_on', 'type' => 'date']);
        Attribute::factory()->create(['code' => 'maker', 'type' => 'select']);

        $this->p1 = makeProduct('OPS-1', $this->family->id, ['common' => [
            'sku' => 'OPS-1', 'unit_price' => '10', 'release_on' => '2024-01-10', 'maker' => 'nike', 'tagline' => 'Air Max runner',
        ]]);
        $this->p2 = makeProduct('OPS-2', $this->family->id, ['common' => [
            'sku' => 'OPS-2', 'unit_price' => '50', 'release_on' => '2024-06-20', 'maker' => 'adidas', 'tagline' => 'Boost trainer',
        ]]);
        $this->p3 = makeProduct('OPS-3', $this->family->id, ['common' => ['sku' => 'OPS-3']]);

        Cache::flush();
    });

    $condition = fn (array $row): array => ['custom_attributes' => [$row]];

    it('filters numbers with less than', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'unit_price', 'operator' => 'less_than', 'value' => '20'])))
            ->toContain('OPS-1')->not->toContain('OPS-2', 'OPS-3');
    });

    it('filters numbers with greater than or equal', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'unit_price', 'operator' => 'greater_than_equal', 'value' => '50'])))
            ->toContain('OPS-2')->not->toContain('OPS-1', 'OPS-3');
    });

    it('filters numbers with between', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'unit_price', 'operator' => 'between', 'value' => '5', 'value2' => '20'])))
            ->toContain('OPS-1')->not->toContain('OPS-2', 'OPS-3');
    });

    it('filters dates with after', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'release_on', 'operator' => 'after', 'value' => '2024-03-01'])))
            ->toContain('OPS-2')->not->toContain('OPS-1', 'OPS-3');
    });

    it('filters dates with between', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'release_on', 'operator' => 'between', 'value' => '2024-01-01', 'value2' => '2024-02-01'])))
            ->toContain('OPS-1')->not->toContain('OPS-2', 'OPS-3');
    });

    it('filters a select with in list', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'maker', 'operator' => 'in', 'value' => ['nike']])))
            ->toContain('OPS-1')->not->toContain('OPS-2', 'OPS-3');
    });

    it('filters a select with not in list and keeps empty values', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'maker', 'operator' => 'not_in', 'value' => ['nike']])))
            ->toContain('OPS-2', 'OPS-3')->not->toContain('OPS-1');
    });

    it('filters with is empty', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'maker', 'operator' => 'empty'])))
            ->toContain('OPS-3')->not->toContain('OPS-1', 'OPS-2');
    });

    it('filters with is not empty', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'maker', 'operator' => 'not_empty'])))
            ->toContain('OPS-1', 'OPS-2')->not->toContain('OPS-3');
    });

    it('filters text with contains', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'tagline', 'operator' => 'contains', 'value' => 'runner'])))
            ->toContain('OPS-1')->not->toContain('OPS-2', 'OPS-3');
    });

    it('defaults a missing operator to in-list matching', function () use ($condition) {
        expect(filteredSkus($condition(['attribute' => 'maker', 'value' => ['adidas']])))
            ->toContain('OPS-2')->not->toContain('OPS-1', 'OPS-3');
    });
});
