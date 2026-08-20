<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Builds a configurable -> simple(variant) tree with a locked gallery, an
 * owned gallery and a locked text attribute.
 */
function lockedMediaFieldFixture(): array
{
    $axisCode = 'axis_'.Str::random(8);
    $lockedGalleryCode = 'lgal_'.Str::random(8);
    $ownedGalleryCode = 'ogal_'.Str::random(8);
    $lockedTextCode = 'ltxt_'.Str::random(8);

    $axis = Attribute::factory()->create(['code' => $axisCode, 'type' => 'select']);

    $lockedGallery = Attribute::factory()->create([
        'code'              => $lockedGalleryCode,
        'type'              => 'gallery',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $ownedGallery = Attribute::factory()->create([
        'code'              => $ownedGalleryCode,
        'type'              => 'gallery',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    Attribute::factory()->create([
        'code'              => $lockedTextCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', [
        $axisCode,
        $lockedGalleryCode,
        $ownedGalleryCode,
        $lockedTextCode,
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

    VariantStructureAttribute::create([
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $ownedGallery->id,
        'level'                => 'variant',
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
            'sku'                => $configurable->sku,
            $lockedGalleryCode   => ['product/parent/locked-cover.jpg', 'product/parent/locked-detail.jpg'],
            $lockedTextCode      => 'PARENT-LOCKED-TEXT-VALUE',
        ],
    ];

    $configurable->save();

    $child = $configurable->getTypeInstance()->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $configurable->id,
        'sku'       => $configurable->sku.'-v1',
        'values'    => ['common' => [$axisCode => $axis->options->first()->code]],
    ]);

    $child = Product::find($child->id);

    $childCommonValues = $child->values['common'] ?? [];
    $childCommonValues[$ownedGalleryCode] = ['product/child/owned-cover.jpg'];

    $child->values = array_merge($child->values ?? [], ['common' => $childCommonValues]);
    $child->save();

    return [
        'configurable'      => $configurable->fresh(),
        'child'             => Product::find($child->id),
        'axisCode'          => $axisCode,
        'lockedGalleryCode' => $lockedGalleryCode,
        'ownedGalleryCode'  => $ownedGalleryCode,
        'lockedTextCode'    => $lockedTextCode,
    ];
}

/**
 * Returns the opening tag of the media component for the given attribute.
 */
function mediaComponentOpenTag(string $html, string $tag, string $attributeCode): string
{
    $pattern = '/<'.preg_quote($tag, '/').'\s+id="'.preg_quote($attributeCode, '/').'"/';

    $matched = preg_match($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

    expect($matched)->toBe(1);

    $start = $matches[0][1];

    $end = strpos($html, '>', $start);

    return substr($html, $start, $end - $start + 1);
}

/**
 * Returns [offset, tag] for the fieldset opening tag preceding a position.
 */
function fieldsetOpenTagBefore(string $html, int $position): array
{
    $fieldsetStart = strrpos(substr($html, 0, $position), '<fieldset');

    expect($fieldsetStart)->not->toBeFalse();

    $tagEnd = strpos($html, '>', $fieldsetStart);

    return [$fieldsetStart, substr($html, $fieldsetStart, $tagEnd - $fieldsetStart + 1)];
}

describe('read-only rendering of locked media attribute fields', function () {
    it('renders v-bind:read-only="true" on a gallery field locked at an ancestor level', function () {
        $this->loginAsAdmin();

        $fixture = lockedMediaFieldFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        $lockedTag = mediaComponentOpenTag($content, 'v-media-gallery', $fixture['lockedGalleryCode']);

        expect($lockedTag)->toContain('v-bind:read-only="true"');
    });

    it('renders v-bind:read-only="false" on a gallery field the product owns at its own level', function () {
        $this->loginAsAdmin();

        $fixture = lockedMediaFieldFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        $ownedTag = mediaComponentOpenTag($content, 'v-media-gallery', $fixture['ownedGalleryCode']);

        expect($ownedTag)->toContain('v-bind:read-only="false"');
    });

    it('does not wrap the locked media field in the disabled fieldset or pointer-events-none div', function () {
        $this->loginAsAdmin();

        $fixture = lockedMediaFieldFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        $lockedTag = mediaComponentOpenTag($content, 'v-media-gallery', $fixture['lockedGalleryCode']);

        $lockedTagPosition = strpos($content, $lockedTag);

        [$fieldsetStart, $fieldsetTag] = fieldsetOpenTagBefore($content, $lockedTagPosition);

        expect($fieldsetTag)->not->toContain('disabled')
            ->and($fieldsetTag)->not->toContain('opacity-60')
            ->and($fieldsetTag)->not->toContain('cursor-not-allowed');

        $wrapperSegment = substr($content, $fieldsetStart, $lockedTagPosition - $fieldsetStart);

        expect($wrapperSegment)->not->toContain('pointer-events-none');
    });

    it('still wraps a non-media locked attribute in the disabled fieldset and pointer-events-none div', function () {
        $this->loginAsAdmin();

        $fixture = lockedMediaFieldFixture();

        $content = $this->get(route('admin.catalog.products.edit', $fixture['child']->id))
            ->assertOk()
            ->getContent();

        $inputPosition = strpos($content, 'name="values[common]['.$fixture['lockedTextCode'].']"');

        expect($inputPosition)->not->toBeFalse();

        [$fieldsetStart, $fieldsetTag] = fieldsetOpenTagBefore($content, $inputPosition);

        expect($fieldsetTag)->toContain('disabled')
            ->and($fieldsetTag)->toContain('opacity-60')
            ->and($fieldsetTag)->toContain('cursor-not-allowed');

        $wrapperSegment = substr($content, $fieldsetStart, $inputPosition - $fieldsetStart);

        expect($wrapperSegment)->toContain('pointer-events-none');
    });
});
