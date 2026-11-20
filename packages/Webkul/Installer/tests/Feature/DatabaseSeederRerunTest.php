<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
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
        'attribute_family_group_mappings', 'categories', 'category_fields', 'category_field_translations',
        'channels', 'channel_translations', 'channel_currencies', 'channel_locales', 'locales', 'currencies',
        'admins', 'roles', 'measurement_families', 'magic_ai_prompts', 'magic_ai_system_prompts',
    ];

    return collect($tables)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
}

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
