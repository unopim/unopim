<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Repositories\ProductRepository;

/**
 * QA-only fixture backing tests/e2e-pw/.../product-discard-changes.spec.js:
 * a family whose group exposes one attribute of each rich type (WYSIWYG,
 * image, gallery, file) plus a Simple product on it, so the discard/revert
 * behaviour of every rich field can be exercised end to end.
 *
 * Names are fixed fixture strings (not `trans()` keys) on purpose — this is
 * throwaway test scaffolding, never shipped UI copy, so it must not add
 * QA-only keys to all 33 locale files. Idempotent by code/sku.
 *
 * Run: php artisan db:seed --class=DiscardQaFixtureSeeder
 */
class DiscardQaFixtureSeeder extends Seeder
{
    private const GROUP_CODE = 'e2e_media_qa';

    private const FAMILY_CODE = 'e2e_media_qa';

    private const PRODUCT_SKU = 'E2E-MEDIA-QA-001';

    /**
     * @var list<array{code: string, type: string, name: string, wysiwyg?: bool, extensions?: list<string>}>
     */
    private const ATTRIBUTES = [
        ['code' => 'e2e_qa_wysiwyg', 'type' => 'textarea', 'name' => 'E2E QA WYSIWYG', 'wysiwyg' => true],
        ['code' => 'e2e_qa_image', 'type' => 'image', 'name' => 'E2E QA Image'],
        ['code' => 'e2e_qa_gallery', 'type' => 'gallery', 'name' => 'E2E QA Gallery'],
        ['code' => 'e2e_qa_file', 'type' => 'file', 'name' => 'E2E QA File', 'extensions' => ['pdf']],
    ];

    public function __construct(
        private readonly AttributeGroupRepository $groups,
        private readonly AttributeRepository $attributes,
        private readonly AttributeFamilyRepository $families,
        private readonly ProductRepository $products,
    ) {}

    public function run(): void
    {
        if (! AttributeGroupProxy::modelClass()::where('code', self::GROUP_CODE)->exists()) {
            $this->groups->create(array_merge(['code' => self::GROUP_CODE], $this->translatedNames('E2E QA Media')));
        }

        foreach (self::ATTRIBUTES as $definition) {
            if (AttributeProxy::modelClass()::where('code', $definition['code'])->exists()) {
                continue;
            }

            $this->attributes->create(array_merge([
                'code'               => $definition['code'],
                'type'               => $definition['type'],
                'enable_wysiwyg'     => ! empty($definition['wysiwyg']) ? 1 : 0,
                'value_per_locale'   => 0,
                'value_per_channel'  => 0,
                'is_required'        => 0,
                'is_unique'          => 0,
                'allowed_extensions' => $definition['extensions'] ?? null,
            ], $this->translatedNames($definition['name'])));
        }

        if (! AttributeFamilyProxy::modelClass()::where('code', self::FAMILY_CODE)->exists()) {
            $groupId = AttributeGroupProxy::modelClass()::where('code', self::GROUP_CODE)->value('id');

            $this->families->create(array_merge([
                'code'             => self::FAMILY_CODE,
                'status'           => 1,
                'attribute_groups' => [
                    $groupId => [
                        'position'          => 1,
                        'custom_attributes' => [
                            ['code' => 'sku'],
                            ['code' => 'e2e_qa_wysiwyg'],
                            ['code' => 'e2e_qa_image'],
                            ['code' => 'e2e_qa_gallery'],
                            ['code' => 'e2e_qa_file'],
                        ],
                    ],
                ],
            ], $this->translatedNames('E2E QA Media')));
        }

        if (! ProductProxy::modelClass()::where('sku', self::PRODUCT_SKU)->exists()) {
            $this->products->create([
                'sku'                 => self::PRODUCT_SKU,
                'type'                => 'simple',
                'attribute_family_id' => AttributeFamilyProxy::modelClass()::where('code', self::FAMILY_CODE)->value('id'),
            ]);
        }
    }

    /**
     * @return array<string, array{name: string}>
     */
    private function translatedNames(string $name): array
    {
        return LocaleProxy::modelClass()::query()
            ->pluck('code')
            ->mapWithKeys(fn (string $localeCode): array => [$localeCode => ['name' => $name]])
            ->all();
    }
}
