<?php

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Webkul\Admin\Tests\Support\InMemoryEmbeddingElasticSearch;
use Webkul\AiAgent\Jobs\IndexProductEmbeddingsJob;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;

function embeddingAttribute(string $code, string $type, array $options = []): Attribute
{
    $attribute = Attribute::query()->where('code', $code)->first()
        ?? Attribute::factory()->create(['code' => $code, 'type' => $type]);

    if ($attribute->type !== $type) {
        $attribute->update(['type' => $type]);
    }

    AttributeOption::query()->where('attribute_id', $attribute->id)->delete();

    foreach ($options as $optionCode => $label) {
        AttributeOption::factory()->create([
            'attribute_id' => $attribute->id,
            'code'         => $optionCode,
            'en_US'        => ['label' => $label],
        ]);
    }

    return $attribute;
}

function headphoneParentValues(): array
{
    return [
        'common' => [
            'sku'            => 'halo-over-ear',
            'url_key'        => 'halo-over-ear',
            'image'          => 'product/1/image/audio-01.webp',
            'spec_sheet'     => 'product/1/spec_sheet/audio.pdf',
            'release_date'   => '2026-02-17',
            'available_from' => '2026-02-17 09:00:00',
            'is_featured'    => true,
            'is_clearance'   => false,
            'features'       => ['noise_cancelling', 'fast_charge'],
            'net_weight'     => ['unit' => 'KILOGRAM', 'amount' => '0.2900', 'family' => 'Weight', 'symbol' => 'kg', 'base_data' => '0.290000', 'base_unit' => 'KILOGRAM'],
        ],
        'categories'      => ['headphones'],
        'locale_specific' => [
            'en_US' => ['highlights' => 'Adaptive ANC · 42 h battery'],
            'fr_FR' => ['highlights' => 'ANC adaptative · 42 h d’autonomie'],
        ],
        'channel_specific' => [
            'default' => ['cost' => ['EUR' => '109', 'USD' => '118']],
        ],
        'channel_locale_specific' => [
            'default' => [
                'en_US' => [
                    'name'        => 'Halo Over-Ear Headphones',
                    'meta_title'  => 'Halo Over-Ear Headphones',
                    'description' => '<p>Halo pairs a 40 mm driver with adaptive noise cancelling.</p>',
                    'price'       => ['EUR' => '309', 'USD' => '329'],
                ],
                'fr_FR' => [
                    'name'        => 'Casque circum-auriculaire Halo',
                    'description' => '<p>Le Halo associe un transducteur de 40 mm.</p>',
                ],
            ],
        ],
    ];
}

function headphoneVariantValues(): array
{
    return [
        'common' => [
            'sku'     => 'halo-over-ear-graphite',
            'url_key' => 'halo-over-ear-graphite',
            'color'   => 'graphite_tone',
            'gallery' => ['product/2/gallery/audio-01-detail.webp'],
        ],
        'channel_locale_specific' => [
            'default' => ['en_US' => ['price' => ['EUR' => '309', 'USD' => '329']]],
        ],
    ];
}

beforeEach(function () {
    embeddingAttribute('highlights', 'textarea');
    embeddingAttribute('spec_sheet', 'file');
    embeddingAttribute('gallery', 'gallery');
    embeddingAttribute('release_date', 'date');
    embeddingAttribute('available_from', 'datetime');
    embeddingAttribute('is_featured', 'boolean');
    embeddingAttribute('is_clearance', 'boolean');
    embeddingAttribute('net_weight', 'measurement');
    embeddingAttribute('features', 'multiselect', ['noise_cancelling' => 'Noise cancelling', 'fast_charge' => 'Fast charge']);

    $color = Attribute::query()->where('code', 'color')->firstOrFail();

    AttributeOption::factory()->create([
        'attribute_id' => $color->id,
        'code'         => 'graphite_tone',
        'en_US'        => ['label' => 'Graphite'],
    ]);
});

it('drops price, measurement internals, file paths, dates and url keys from the document', function () {
    $text = (new ProductEmbeddingDocumentBuilder)->build(1, 'halo-over-ear', headphoneParentValues())['text'];

    expect($text)
        ->toContain('name: Halo Over-Ear Headphones')
        ->toContain('description: Halo pairs a 40 mm driver with adaptive noise cancelling.')
        ->toContain('net_weight: 0.29 kg')
        ->toContain('features: Noise cancelling, Fast charge')
        ->toContain("\nis_featured")
        ->not->toContain('is_clearance')
        ->not->toContain('USD')
        ->not->toContain('EUR')
        ->not->toContain('309')
        ->not->toContain('base_data')
        ->not->toContain('base_unit')
        ->not->toContain('KILOGRAM')
        ->not->toContain('product/1/')
        ->not->toContain('2026-02-17')
        ->not->toContain('url_key')
        ->not->toContain('meta_title');
});

it('embeds a single locale of the product text', function () {
    $text = (new ProductEmbeddingDocumentBuilder)->build(1, 'halo-over-ear', headphoneParentValues())['text'];

    expect($text)
        ->toContain('name: Halo Over-Ear Headphones')
        ->toContain('highlights: Adaptive ANC · 42 h battery')
        ->not->toContain('Casque')
        ->not->toContain('autonomie')
        ->not->toContain('transducteur');
});

it('falls back to another locale when the default locale has no text', function () {
    $values = headphoneParentValues();
    unset($values['channel_locale_specific']['default']['en_US'], $values['locale_specific']['en_US']);

    $text = (new ProductEmbeddingDocumentBuilder)->build(1, 'halo-over-ear', $values)['text'];

    expect($text)
        ->toContain('name: Casque circum-auriculaire Halo')
        ->toContain('highlights: ANC adaptative')
        ->not->toContain('Halo Over-Ear Headphones');
});

it('merges the parent text into a variant document', function () {
    $document = (new ProductEmbeddingDocumentBuilder)->build(2, 'halo-over-ear-graphite', headphoneVariantValues(), headphoneParentValues());

    expect($document['text'])
        ->toStartWith('sku: halo-over-ear-graphite')
        ->toContain('name: Halo Over-Ear Headphones')
        ->toContain('description: Halo pairs a 40 mm driver')
        ->toContain('color: Graphite')
        ->not->toContain('sku: halo-over-ear'."\n")
        ->not->toContain('product/2/')
        ->not->toContain('329')
        ->and($document['content_hash'])->toBe(hash('sha256', $document['text']));

    expect(strpos($document['text'], 'color: Graphite'))->toBeGreaterThan(strpos($document['text'], 'name: Halo Over-Ear Headphones'));
});

it('embeds variants with their parent text from the indexing job', function () {
    $parent = Product::factory()->configurable()->create(['sku' => 'halo-over-ear', 'values' => headphoneParentValues()]);
    $variant = Product::factory()->simple()->create([
        'sku'       => 'halo-over-ear-graphite',
        'parent_id' => $parent->id,
        'values'    => headphoneVariantValues(),
    ]);

    config(['ai-agent.vector_store.enabled' => true, 'elasticsearch.enabled' => true]);

    (new InMemoryEmbeddingElasticSearch)->install();

    Embeddings::fake([[array_fill(0, 8, 0.5)]]);

    (new IndexProductEmbeddingsJob([$variant->id]))->handle(new ProductEmbeddingIndex, new ProductEmbeddingDocumentBuilder);

    Embeddings::assertGenerated(fn ($prompt): bool => str_contains($prompt->inputs[0], 'sku: halo-over-ear-graphite')
        && str_contains($prompt->inputs[0], 'name: Halo Over-Ear Headphones')
        && str_contains($prompt->inputs[0], 'color: Graphite')
        && ! str_contains($prompt->inputs[0], 'USD'));
});

it('leads with the name and keeps descriptive copy over its seo duplicate', function () {
    $values = headphoneParentValues();
    $values['channel_locale_specific']['default']['en_US']['meta_description'] = 'Adaptive over-ear headphones.';
    $values['channel_locale_specific']['default']['en_US']['short_description'] = 'Adaptive over-ear headphones.';

    $text = (new ProductEmbeddingDocumentBuilder)->build(1, 'halo-over-ear', $values)['text'];

    expect($text)
        ->toStartWith("sku: halo-over-ear\nname: Halo Over-Ear Headphones\n")
        ->toContain('short_description: Adaptive over-ear headphones.')
        ->not->toContain('meta_description');
});

it('builds a batch of documents with a constant number of queries', function () {
    $builder = new ProductEmbeddingDocumentBuilder;
    $row = fn (int $id): array => ['id' => $id, 'sku' => 'halo-'.$id, 'values' => headphoneVariantValues(), 'parent_values' => headphoneParentValues()];

    $builder->buildMany([$row(1)]);

    DB::enableQueryLog();
    $builder->buildMany([$row(1)]);
    $single = count(DB::getQueryLog());

    DB::flushQueryLog();
    $builder->buildMany(array_map($row, range(1, 25)));
    $batch = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($batch)->toBe($single);
});
