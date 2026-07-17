<?php

use Webkul\AiAgent\Jobs\TranslateProductValuesJob;

/**
 * Invoke a protected method on the job without booting the database.
 */
function invokeJobMethod(TranslateProductValuesJob $job, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($job, $method);
    $ref->setAccessible(true);

    return $ref->invokeArgs($job, $args);
}

it('excludes the source locale and de-duplicates target locales across channels', function () {
    $job = new TranslateProductValuesJob(1, 'en_US', []);

    $channels = [
        (object) ['locales' => [(object) ['code' => 'en_US'], (object) ['code' => 'fr_FR']]],
        (object) ['locales' => [(object) ['code' => 'fr_FR'], (object) ['code' => 'de_DE']]],
    ];

    $result = invokeJobMethod($job, 'resolveTargetLocales', [$channels]);

    expect($result)->toBe(['fr_FR', 'de_DE']);
});

it('keeps only translatable string fields, skipping identifiers and empty values', function () {
    $job = new TranslateProductValuesJob(1, 'en_US', [
        'name'           => 'Shoe',
        'sku'            => 'SKU-1',        // identifier — never translated
        'url_key'        => 'shoe',         // identifier — never translated
        'product_number' => 'PN-1',         // identifier — never translated
        'image'          => 'a.jpg',        // asset — never translated
        'description'    => 'Red leather',
        'price'          => 50,             // non-string — skipped
        'meta_title'     => '',             // empty — skipped
    ]);

    $result = invokeJobMethod($job, 'collectTranslatableFields');

    expect($result)->toBe([
        'name'        => 'Shoe',
        'description' => 'Red leather',
    ]);
});
