<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Category\Models\Category;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as InstallerSeeder;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

/**
 * Count the rows of every table the installer seeder writes to.
 *
 * @return array<string, int>
 */
function seededTableCounts(): array
{
    $tables = [
        'attribute_groups', 'attribute_group_translations', 'attributes', 'attribute_translations',
        'attribute_options', 'attribute_option_translations', 'attribute_families', 'attribute_family_translations',
        'attribute_family_group_mappings', 'attribute_group_mappings', 'categories', 'category_fields', 'category_field_translations',
        'channels', 'channel_translations', 'channel_currencies', 'channel_locales', 'locales', 'currencies',
        'admins', 'roles', 'measurement_families', 'magic_ai_prompts', 'magic_ai_system_prompts',
    ];

    return collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
}

beforeEach(function (): void {
    DatabaseSequenceHelper::fixSequences(['admins', 'attributes', 'categories', 'products']);
});

it('keeps custom attributes and every seeded table unchanged when the seeder runs again', function (): void {
    $customAttribute = Attribute::factory()->create(['code' => 'custom_attribute_test', 'type' => 'text']);
    $customCategory = Category::factory()->create();
    $secondAdmin = Admin::factory()->create();

    $before = seededTableCounts();

    $this->seed(InstallerSeeder::class);

    expect(seededTableCounts())->toBe($before);

    $this->assertDatabaseHas('attributes', ['id' => $customAttribute->id, 'code' => 'custom_attribute_test']);
    $this->assertDatabaseHas('categories', ['id' => $customCategory->id]);
    $this->assertDatabaseHas('admins', ['id' => $secondAdmin->id]);
});

it('runs again without failing on an install that has products', function (): void {
    $product = Product::factory()->simple()->create();

    $this->seed(InstallerSeeder::class);

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

it('keeps existing measurement families instead of recreating them', function (): void {
    $family = MeasurementFamily::query()->orderBy('id')->firstOrFail();

    $this->seed(InstallerSeeder::class);

    $this->assertDatabaseHas('measurement_families', ['id' => $family->id, 'code' => $family->code]);
});

it('treats existing option codes as the defaults regardless of letter case', function (): void {
    $sizeId = Attribute::query()->where('code', 'size')->value('id');

    DB::table('attribute_options')->where('attribute_id', $sizeId)->delete();

    foreach (['s', 'm', 'l', 'xl'] as $position => $code) {
        DB::table('attribute_options')->insert(['attribute_id' => $sizeId, 'code' => $code, 'sort_order' => $position + 1]);
    }

    $this->seed(InstallerSeeder::class);

    expect(DB::table('attribute_options')->where('attribute_id', $sizeId)->orderBy('sort_order')->pluck('code')->all())
        ->toBe(['s', 'm', 'l', 'xl']);
});

it('does not add default options to an attribute that is no longer a select', function (): void {
    $colorId = Attribute::query()->where('code', 'color')->value('id');

    DB::table('attribute_options')->where('attribute_id', $colorId)->delete();

    Attribute::query()->whereKey($colorId)->update(['type' => 'text']);

    $this->seed(InstallerSeeder::class);

    $this->assertDatabaseMissing('attribute_options', ['attribute_id' => $colorId]);
    $this->assertDatabaseHas('attributes', ['id' => $colorId, 'code' => 'color', 'type' => 'text']);
});

it('restores the default attributes, options, groups and family mappings deleted from the catalog', function (): void {
    $customAttribute = Attribute::factory()->create(['code' => 'custom_attribute_test', 'type' => 'text']);

    DB::table('product_super_attributes')->delete();

    Attribute::query()->whereNotIn('code', ['sku', 'custom_attribute_test'])->delete();
    AttributeGroup::query()->where('code', 'media')->delete();

    $this->seed(InstallerSeeder::class);

    $color = Attribute::query()->where('code', 'color')->firstOrFail();
    $size = Attribute::query()->where('code', 'size')->firstOrFail();

    expect(Attribute::query()->where('code', '!=', 'custom_attribute_test')->count())->toBe(21)
        ->and(DB::table('attribute_options')->where('attribute_id', $color->id)->orderBy('sort_order')->pluck('code')->all())
        ->toBe(['Red', 'Green', 'Yellow', 'Black', 'White'])
        ->and(DB::table('attribute_options')->where('attribute_id', $size->id)->orderBy('sort_order')->pluck('code')->all())
        ->toBe(['S', 'M', 'L', 'XL'])
        ->and(DB::table('attribute_translations')->where('attribute_id', $color->id)->exists())->toBeTrue()
        ->and(DB::table('attribute_group_translations')->where('attribute_group_id', AttributeGroup::query()->where('code', 'media')->value('id'))->exists())->toBeTrue();

    $familyAttributeCodes = DB::table('attribute_group_mappings')
        ->join('attribute_family_group_mappings', 'attribute_family_group_mappings.id', '=', 'attribute_group_mappings.attribute_family_group_id')
        ->join('attribute_families', 'attribute_families.id', '=', 'attribute_family_group_mappings.attribute_family_id')
        ->join('attributes', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
        ->where('attribute_families.code', 'default')
        ->pluck('attributes.code')
        ->sort()
        ->values()
        ->all();

    expect($familyAttributeCodes)->toBe([
        'brand',
        'color',
        'cost',
        'description',
        'image',
        'meta_description',
        'meta_keywords',
        'meta_title',
        'name',
        'price',
        'product_number',
        'short_description',
        'size',
        'sku',
        'tax_category_id',
        'url_key',
    ]);

    $this->assertDatabaseHas('attributes', ['id' => $customAttribute->id, 'code' => 'custom_attribute_test']);
});
