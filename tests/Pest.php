<?php

use Illuminate\Support\Str;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\AdminApi\Tests\ApiTestCase;
use Webkul\Attribute\Tests\AttributeTestCase;
use Webkul\Category\Tests\CategoryTestCase;
use Webkul\Completeness\Tests\CompletenessTestCase;
use Webkul\Core\Models\Channel;
use Webkul\Core\Tests\CoreTestCase;
use Webkul\DataGrid\Tests\DataGridTestCase;
use Webkul\Installer\Tests\UserCreateCommandTestCase;
use Webkul\Measurement\Tests\MeasurementTestCase;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\ProductTestCase;
use Webkul\ProductPassport\Tests\ProductPassportTestCase;
use Webkul\Publication\Tests\PublicationTestCase;
use Webkul\Resource\Tests\ResourceTestCase;
use Webkul\User\Tests\UserTestCase;

ini_set('memory_limit', getenv('PEST_MEMORY_LIMIT') ?: '1024M');

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(CoreTestCase::class)->in('../packages/Webkul/Core/tests');
uses(AdminTestCase::class)->in('../packages/Webkul/Admin/tests');
uses(ApiTestCase::class)->in('../packages/Webkul/AdminApi/tests');
uses(UserTestCase::class)->in('../packages/Webkul/User/tests');
uses(DataGridTestCase::class)->in('../packages/Webkul/DataGrid/tests');
uses(UserCreateCommandTestCase::class)->in('../packages/Webkul/Installer/tests');
uses(CoreTestCase::class)->in('../packages/Webkul/ElasticSearch/tests');
uses(AdminTestCase::class)->in('../packages/Webkul/DataTransfer/tests');
uses(CompletenessTestCase::class)->in('../packages/Webkul/Completeness/tests');
uses(ProductTestCase::class)->in('../packages/Webkul/Product/tests');
uses(AttributeTestCase::class)->in('../packages/Webkul/Attribute/tests');
uses(CategoryTestCase::class)->in('../packages/Webkul/Category/tests');
uses(MeasurementTestCase::class)->in('../packages/Webkul/Measurement/tests');
uses(CoreTestCase::class)->in('../packages/Webkul/AiAgent/tests');
uses(CoreTestCase::class)->in('../packages/Webkul/Webhook/tests');
uses(AdminTestCase::class)->in('../packages/Webkul/AppUrlGuard/tests');
uses(ResourceTestCase::class)->in('../packages/Webkul/Resource/tests/Feature');
uses(PublicationTestCase::class)->in('../packages/Webkul/Publication/tests');
uses(ProductPassportTestCase::class)->in('../packages/Webkul/ProductPassport/tests');

/*
|--------------------------------------------------------------------------
| Test Impact Analysis
|--------------------------------------------------------------------------
|
| Coverage edges only capture executed PHP, so assets that are compiled or read at runtime
| (Blade views, language files, JS) would otherwise look untouched to TIA. Map each package's
| non-PHP sources back to its own test directory, and treat schema changes as global.
|
*/

$packageTestPaths = array_map(
    fn (string $path): string => 'packages/Webkul/'.basename(dirname($path)).'/tests',
    glob(dirname(__DIR__).'/packages/Webkul/*/tests', GLOB_ONLYDIR) ?: [],
);

foreach ($packageTestPaths as $testPath) {
    $package = dirname($testPath);

    pest()->tia()->baselined();

    pest()->tia()->watch([
        $package.'/src/Resources/views/**'  => $testPath,
        $package.'/src/Resources/lang/**'   => $testPath,
        $package.'/src/Resources/assets/**' => $testPath,
        $package.'/src/Config/**'           => $testPath,
    ]);

    pest()->tia()->watch([
        'packages/Webkul/*/src/Database/Migration/**' => $testPath,
        'database/seeders/**'                         => $testPath,
    ]);
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Seed the family's required attribute values on a factory product.
 *
 * A save is checked against every required attribute of the family, not only the ones the request
 * carried, so a bare factory product cannot be updated with a partial payload until these exist.
 *
 * @return Product
 */
function seedRequiredProductValues($product)
{
    $scoped = Channel::with(['locales', 'currencies'])->get()->mapWithKeys(fn ($channel) => [
        $channel->code => $channel->locales->pluck('code')->mapWithKeys(fn ($locale) => [
            $locale => [
                'name'              => 'Test Product '.$product->sku,
                'short_description' => 'Short description',
                'description'       => 'Description',
                'price'             => $channel->currencies->pluck('code')
                    ->mapWithKeys(fn ($code) => [$code => '100'])
                    ->all(),
            ],
        ])->all(),
    ])->all();

    $product->values = array_replace_recursive([
        'common' => [
            'sku'     => $product->sku,
            'url_key' => Str::slug($product->sku),
            'weight'  => '1',
        ],
        'channel_locale_specific' => $scoped,
    ], $product->values ?? []);

    $product->save();

    return $product;
}
