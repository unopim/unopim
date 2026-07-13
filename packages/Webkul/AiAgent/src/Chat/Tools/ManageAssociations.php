<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;

class ManageAssociations implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'manage_associations';
            }

            public function description(): string
            {
                return 'Add or replace product associations: the 3 built-in types (related, up-sell, cross-sell) via the legacy comma-separated params, or ANY active association type (including custom types like "bundle_kit") with optional per-link field values (e.g. quantity) via associations_json. Defaults to append mode which keeps existing associations.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'               => $schema->string()->description('The product SKU to update associations for'),
                    'related'           => $schema->string()->description('Comma-separated SKUs for related products (leave empty to skip)'),
                    'up_sells'          => $schema->string()->description('Comma-separated SKUs for up-sell products (leave empty to skip)'),
                    'cross_sells'       => $schema->string()->description('Comma-separated SKUs for cross-sell products (leave empty to skip)'),
                    'associations_json' => $schema->string()->description('JSON array to set associations of ANY active type (built-in or custom). Each item: {"association_type": "<type code, e.g. bundle_kit>", "related_sku": "<SKU>", "additional_data": {"<field_code>": "<value>", ...}}. additional_data is optional and is validated against that association type\'s configured custom fields.'),
                    'mode'              => $schema->string()->enum(['append', 'replace'])->description('append (default) keeps existing links and adds the new ones; replace sets each given association type\'s links to exactly the ones provided. Use append unless user explicitly asks to replace.'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString();
                $mode = $request->string('mode')->toString() ?: 'append';

                $productRepo = app('Webkul\Product\Repositories\ProductRepository');
                $product = $productRepo->findOneByField('sku', $sku);

                if (! $product) {
                    return json_encode(['error' => "SKU not found: {$sku}"]);
                }

                [$linksByType, $decodeError] = $this->collectRequestedLinks($request);

                if ($decodeError) {
                    return json_encode(['error' => $decodeError]);
                }

                if (empty($linksByType)) {
                    return json_encode(['error' => 'No valid associations to apply.']);
                }

                $errors = [];

                $associationTypeRepository = app(AssociationTypeRepository::class);

                foreach (array_keys($linksByType) as $typeCode) {
                    $associationType = $associationTypeRepository->findByCode($typeCode);

                    if (! $associationType || (int) $associationType->status !== 1) {
                        $errors[] = "Unknown or inactive association type: {$typeCode}";
                        unset($linksByType[$typeCode]);
                    }
                }

                if (empty($linksByType)) {
                    return json_encode(['error' => 'No valid association types. Errors: '.implode('; ', $errors)]);
                }

                // Report SKUs that don't resolve to a product (or self-reference
                // the source product) up front, mirroring the previous tool's
                // UX; resolution itself is delegated to prepareRichAssociations()
                // below, which silently skips unresolved/self-referencing SKUs.
                $requestedSkus = collect($linksByType)->flatten(1)->pluck('sku')->unique()->values()->all();

                $validSkus = empty($requestedSkus) ? [] : DB::table('products')
                    ->whereIn('sku', $requestedSkus)
                    ->where('sku', '!=', $sku)
                    ->pluck('sku')
                    ->toArray();

                $invalidSkus = array_diff($requestedSkus, $validSkus);

                if (! empty($invalidSkus)) {
                    $errors[] = 'SKUs not found or self-referencing: '.implode(', ', $invalidSkus);
                }

                // Reuse the same validate-then-resolve rich-association logic the
                // AdminApi REST controllers use (Task 6) instead of duplicating
                // it: validates every link's additional_data against the type's
                // configured fields (throws before anything is persisted) and
                // resolves each sku to a related_product_id.
                try {
                    [$legacySkuLists, $resolvedAssociations] = $product->getTypeInstance()->prepareRichAssociations($linksByType, $product);
                } catch (ValidationException $e) {
                    return json_encode(['error' => 'Invalid association field values: '.implode(' ', $e->validator->errors()->all())]);
                }

                if (empty($resolvedAssociations)) {
                    return json_encode(['error' => 'No valid associations to apply. Errors: '.implode('; ', $errors)]);
                }

                if ($mode === 'replace') {
                    // Whole-type replace: each given type's links become exactly
                    // the provided set (same call the REST controller makes).
                    $product->getTypeInstance()->syncRichAssociations($product->id, $resolvedAssociations);
                } else {
                    // Append: upsert each link individually so other, unrelated
                    // links of the same type are left untouched.
                    $associationRepository = app(ProductAssociationRepository::class);

                    foreach ($resolvedAssociations as $associationData) {
                        foreach ($associationData['links'] as $link) {
                            $associationRepository->upsertLink(
                                $product->id,
                                $associationData['association_type_id'],
                                $link['related_product_id'],
                                $link['position'],
                                $link['additional_data']
                            );
                        }
                    }
                }

                // Dual-write: mirror the 3 legacy sections into the legacy
                // values['associations'] JSON so readers that haven't migrated
                // to the product_associations link table stay consistent.
                if (! empty($legacySkuLists)) {
                    $values = $product->values ?? [];

                    foreach ($legacySkuLists as $section => $skus) {
                        if ($mode === 'append') {
                            $existing = $values['associations'][$section] ?? [];
                            $skus = array_values(array_unique(array_merge(
                                is_array($existing) ? $existing : [],
                                $skus
                            )));
                        }

                        $values['associations'][$section] = $skus;
                    }

                    $product->values = $values;
                    $product->save();
                }

                $summary = [];

                foreach ($resolvedAssociations as $typeCode => $associationData) {
                    $summary[$typeCode] = count($associationData['links']);
                }

                return json_encode([
                    'result' => [
                        'sku'          => $sku,
                        'associations' => $summary,
                        'mode'         => $mode,
                        'errors'       => empty($errors) ? null : $errors,
                    ],
                ]);
            }

            /**
             * Merges the legacy scalar params (related/up_sells/cross_sells)
             * and the dynamic associations_json param into the unified
             * `{ <typeCode>: [ {sku, additional_data?}, ... ] }` shape consumed
             * by `AbstractType::prepareRichAssociations()`.
             *
             * @return array{0: array<string, array<int, array{sku: string, additional_data?: array}>>, 1: string|null}
             */
            protected function collectRequestedLinks(Request $request): array
            {
                $linksByType = [];

                $legacyInputs = [
                    'related_products' => $request->string('related')->toString(),
                    'up_sells'         => $request->string('up_sells')->toString(),
                    'cross_sells'      => $request->string('cross_sells')->toString(),
                ];

                foreach ($legacyInputs as $typeCode => $skuString) {
                    if (empty($skuString)) {
                        continue;
                    }

                    foreach (array_filter(array_map('trim', explode(',', $skuString))) as $relatedSku) {
                        $linksByType[$typeCode][] = ['sku' => $relatedSku];
                    }
                }

                $associationsJson = $request->string('associations_json')->toString();

                if (! empty($associationsJson)) {
                    $decoded = json_decode($associationsJson, true);

                    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                        return [[], 'associations_json must be a valid JSON array'];
                    }

                    foreach ($decoded as $item) {
                        if (
                            ! is_array($item)
                            || empty($item['association_type'])
                            || empty($item['related_sku'])
                        ) {
                            continue;
                        }

                        $link = ['sku' => (string) $item['related_sku']];

                        if (! empty($item['additional_data']) && is_array($item['additional_data'])) {
                            $link['additional_data'] = ['common' => $item['additional_data']];
                        }

                        $linksByType[(string) $item['association_type']][] = $link;
                    }
                }

                return [$linksByType, null];
            }
        };
    }
}
