<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\CalculatedAttribute\Services\CategoryPathResolver;
use Webkul\Category\Models\Category;
use Webkul\Completeness\DataGrids\AttributeCompletenessDataGrid;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;

it('keeps calculated attributes out of the completeness attribute picker', function (): void {
    $calculated = Attribute::factory()->create([
        'code'    => 'calc_'.Str::random(6),
        'type'    => 'calculated',
        'formula' => 'upper(sku)',
    ]);

    $plain = Attribute::factory()->create([
        'code' => 'plain_'.Str::random(6),
        'type' => 'text',
    ]);

    /*
     * Read the grid's own builder rather than rendering the grid, so the
     * assertion is about the SQL filter and not about family wiring. The joins
     * are dropped so both attributes are in scope regardless of family
     * membership; only the type filter is under test.
     */
    $query = app(AttributeCompletenessDataGrid::class)
        ->setAttributeFamilyId(0)
        ->prepareQueryBuilder();

    $wheres = collect($query->wheres ?? [])
        ->filter(fn (array $where): bool => ($where['column'] ?? null) === 'attributes.type')
        ->values();

    expect($wheres)->toHaveCount(1);
    expect($wheres[0]['operator'])->toBe('!=');
    expect($wheres[0]['value'])->toBe('calculated');

    $visible = DB::table('attributes')
        ->where('attributes.type', '!=', 'calculated')
        ->whereIn('code', [$calculated->code, $plain->code])
        ->pluck('code')
        ->all();

    expect($visible)->toBe([$plain->code]);
});

it('discards a calculated column instead of writing it during import', function (): void {
    $calculatedCode = 'calc_'.Str::random(6);
    $plainCode = 'plain_'.Str::random(6);

    $calculated = Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => 'upper(sku)',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $plain = Attribute::factory()->create([
        'code'              => $plainCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    $importer = app(Importer::class);

    /*
     * Drive the generic value-mapping pass directly. A full import run would
     * pull in job tracks, batching and a file source without telling us
     * anything more about whether this one column is written.
     */
    $reflection = new ReflectionClass($importer);

    $setPrivate = function (string $property, mixed $value) use ($reflection, $importer): void {
        $handle = $reflection->getProperty($property);
        $handle->setAccessible(true);
        $handle->setValue($importer, $value);
    };

    // The property is typed to the JobTrack contract, so a stub will not do.
    $setPrivate('import', new JobTrack(['images_directory_path' => null]));

    // Pre-seed the caches so the pass runs without a real family in the DB.
    $setPrivate('typeFamilyAttributes', [
        'simple' => ['fam' => collect([$calculated, $plain])],
    ]);

    $setPrivate('typeFamilyAttributesByCode', [
        'simple|fam' => [$calculatedCode => $calculated, $plainCode => $plain],
    ]);

    $setPrivate('attributeScopeCache', [
        'simple|fam'                  => true,
        'simple|fam|'.$calculatedCode => 'common',
        'simple|fam|'.$plainCode      => 'common',
    ]);

    $map = $reflection->getMethod('prepareNonMeasurementAttributeValues');
    $map->setAccessible(true);

    $values = [];

    $map->invokeArgs($importer, [
        [
            'type'             => 'simple',
            'attribute_family' => 'fam',
            'sku'              => 'CALC-1',
            $calculatedCode    => 'Injected',
            $plainCode         => 'Stored',
        ],
        &$values,
    ]);

    expect($values['common'][$plainCode] ?? null)->toBe('Stored');
    expect($values['common'])->not->toHaveKey($calculatedCode);
});

it('resolves a real category label path through the database resolver', function (): void {
    $root = Category::whereIsRoot()->first() ?? Category::create(['code' => 'root_'.Str::random(6)]);

    $locale = config('app.locale');

    $parent = Category::create(['code' => 'ak_'.Str::random(6), 'parent_id' => $root->id]);
    $parent->additional_data = ['locale_specific' => [$locale => ['name' => 'Arbeitskleidung']]];
    $parent->save();

    $child = Category::create(['code' => 'az_'.Str::random(6), 'parent_id' => $parent->id]);
    $child->additional_data = ['locale_specific' => [$locale => ['name' => 'Anzug']]];
    $child->save();

    $resolver = app(CategoryPathResolver::class);
    $resolver->flush();

    expect($resolver->deepestPath([$child->code, $parent->code], $locale))
        ->toBe(['Arbeitskleidung', 'Anzug']);

    expect($resolver->deepestPath([$child->code]))
        ->toBe([$parent->code, $child->code]);

    expect($resolver->labels([$child->code], $locale))->toBe(['Anzug']);
});

it('excludes the technical root from the category path', function (): void {
    $root = Category::whereIsRoot()->first() ?? Category::create(['code' => 'root_'.Str::random(6)]);

    $child = Category::create(['code' => 'solo_'.Str::random(6), 'parent_id' => $root->id]);

    $resolver = app(CategoryPathResolver::class);
    $resolver->flush();

    expect($resolver->deepestPath([$child->code]))->toBe([$child->code]);
});

it('stores the formula on the attribute row', function (): void {
    $code = 'calc_'.Str::random(6);

    Attribute::factory()->create([
        'code'    => $code,
        'type'    => 'calculated',
        'formula' => "category_label_path('#')",
    ]);

    expect(DB::table('attributes')->where('code', $code)->value('formula'))
        ->toBe("category_label_path('#')");
});

it('registers the calculated attribute type', function (): void {
    expect(config('attribute_types'))->toHaveKey('calculated');
});
