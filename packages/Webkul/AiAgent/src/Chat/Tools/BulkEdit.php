<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class BulkEdit implements PimTool
{
    public function __construct(
        protected ProductWriterService $writerService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $writerService = $this->writerService;

        return new class($context, $writerService) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected ProductWriterService $writerService)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'bulk_edit';
            }

            public function description(): string
            {
                return 'Bulk update products matching a rule/filter. Supports setting values OR transforming existing values (append/prepend/replace). Use changes_json for setting values, transforms_json for modifying existing values.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'filter_by'       => $schema->string()->description('Filter products by')->enum(['status', 'category', 'family', 'all']),
                    'filter_value'    => $schema->string()->description('Filter value (e.g. "active", category code, family code)'),
                    'changes_json'    => $schema->string()->description('JSON of attribute changes to SET (e.g. {"status":"inactive","brand":"Nike"})'),
                    'transforms_json' => $schema->string()->description('JSON of attribute transforms to MODIFY existing values. Each entry: {"attribute_code": {"action": "append|prepend|replace", "value": "text", "search": "old text (for replace)"}}. Example: {"url_key": {"action": "append", "value": "-webkul"}}'),
                    'limit'           => $schema->integer()->description('Max products to update (default 50, max 500)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.mass_update')) {
                    return $denied;
                }

                $filter_by = $request->string('filter_by')->toString() ?: 'all';
                $filter_value = $request->string('filter_value')->toString() ?: null;
                $changes_json = $request->string('changes_json')->toString() ?: null;
                $transforms_json = $request->string('transforms_json')->toString() ?: null;
                $limit = $request->has('limit') ? (int) $request->get('limit') : 50;

                $changes = $changes_json ? json_decode($changes_json, true) : [];
                $transforms = $transforms_json ? json_decode($transforms_json, true) : [];

                if (empty($changes) && empty($transforms)) {
                    return json_encode(['error' => 'Either changes_json or transforms_json is required']);
                }

                $limit = min(max($limit, 1), 100);

                $grammar = GrammarQueryManager::getGrammar();

                $qb = DB::table('products')->select('id', 'sku', 'attribute_family_id', 'values', 'status');

                if ($filter_by === 'status' && $filter_value !== null) {
                    $isActive = \in_array(strtolower($filter_value), ['active', 'enabled', '1', 'yes', 'on'], true);
                    $qb->where('status', $isActive ? 1 : 0);
                } elseif ($filter_by === 'category' && $filter_value) {
                    $qb->whereRaw($grammar->jsonContains('values', ['categories'], '?'), ['"'.$filter_value.'"']);
                } elseif ($filter_by === 'family' && $filter_value) {
                    $familyId = DB::table('attribute_families')->where('code', $filter_value)->value('id');
                    if (! $familyId) {
                        return json_encode(['error' => "Family '{$filter_value}' not found"]);
                    }
                    $qb->where('attribute_family_id', $familyId);
                }

                $products = $qb->limit($limit)->get();

                if ($products->isEmpty()) {
                    return json_encode(['error' => 'No products match the filter']);
                }

                $updated = 0;
                $errors = [];
                $repo = app('Webkul\Product\Repositories\ProductRepository');
                $currencies = DB::table('currencies')->where('status', 1)->pluck('code')->toArray() ?: ['USD'];

                foreach ($products as $p) {
                    try {
                        $values = json_decode($p->values, true) ?? [];
                        $familyAttrs = $this->writerService->getFamilyAttributesPublic($p->attribute_family_id);
                        $statusChanged = false;

                        foreach ($changes as $code => $value) {
                            if ($code === 'status') {
                                $statusValue = \in_array(strtolower((string) $value), ['1', 'active', 'yes', 'on', 'enabled'], true) ? 1 : 0;
                                DB::table('products')->where('id', $p->id)->update(['status' => $statusValue]);
                                $statusChanged = true;

                                continue;
                            }

                            if (! isset($familyAttrs[$code])) {
                                continue;
                            }

                            $meta = $familyAttrs[$code];

                            if ($meta['type'] === 'price' && is_numeric($value)) {
                                $priceObj = [];
                                foreach ($currencies as $c) {
                                    $priceObj[$c] = (string) round((float) $value, 2);
                                }
                                $value = $priceObj;
                            }

                            if (\in_array($meta['type'], ['select', 'multiselect']) && is_string($value)) {
                                $resolved = $this->writerService->resolveSelectValuePublic($code, $value, $meta['attribute_id']);
                                if ($resolved === null) {
                                    continue;
                                }
                                $value = $resolved;
                            }

                            if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                                $values['channel_locale_specific'][$this->context->channel][$this->context->locale][$code] = $value;
                            } elseif ($meta['value_per_channel']) {
                                $values['channel_specific'][$this->context->channel][$code] = $value;
                            } elseif ($meta['value_per_locale']) {
                                $values['locale_specific'][$this->context->locale][$code] = $value;
                            } else {
                                $values['common'][$code] = $value;
                            }
                        }

                        // Apply transforms (append/prepend/replace on existing values)
                        foreach ($transforms as $code => $transform) {
                            if (! is_array($transform) || empty($transform['action'])) {
                                continue;
                            }

                            $action = $transform['action'];
                            $transformValue = $transform['value'] ?? '';

                            // Read the current value from the correct bucket
                            $currentValue = $values['common'][$code] ?? null;

                            if ($currentValue === null && isset($familyAttrs[$code])) {
                                $meta = $familyAttrs[$code];
                                if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                                    $currentValue = $values['channel_locale_specific'][$this->context->channel][$this->context->locale][$code] ?? null;
                                } elseif ($meta['value_per_channel']) {
                                    $currentValue = $values['channel_specific'][$this->context->channel][$code] ?? null;
                                } elseif ($meta['value_per_locale']) {
                                    $currentValue = $values['locale_specific'][$this->context->locale][$code] ?? null;
                                }
                            }

                            if (! is_string($currentValue)) {
                                continue;
                            }

                            // Apply the transformation
                            $newValue = match ($action) {
                                'append'  => str_ends_with($currentValue, $transformValue) ? $currentValue : $currentValue.$transformValue,
                                'prepend' => str_starts_with($currentValue, $transformValue) ? $currentValue : $transformValue.$currentValue,
                                'replace' => isset($transform['search']) ? str_replace($transform['search'], $transformValue, $currentValue) : $transformValue,
                                default   => $currentValue,
                            };

                            // Write back to the correct bucket
                            if (isset($familyAttrs[$code])) {
                                $meta = $familyAttrs[$code];
                                if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                                    $values['channel_locale_specific'][$this->context->channel][$this->context->locale][$code] = $newValue;
                                } elseif ($meta['value_per_channel']) {
                                    $values['channel_specific'][$this->context->channel][$code] = $newValue;
                                } elseif ($meta['value_per_locale']) {
                                    $values['locale_specific'][$this->context->locale][$code] = $newValue;
                                } else {
                                    $values['common'][$code] = $newValue;
                                }
                            } else {
                                $values['common'][$code] = $newValue;
                            }
                        }

                        $repo->updateWithValues(['values' => $values], $p->id);
                        $updated++;
                    } catch (\Throwable $e) {
                        $errors[] = "SKU {$p->sku}: {$e->getMessage()}";
                    }
                }

                return json_encode([
                    'result' => [
                        'matched' => $products->count(),
                        'updated' => $updated,
                        'filter'  => "{$filter_by}={$filter_value}",
                        'errors'  => empty($errors) ? null : array_slice($errors, 0, 5),
                    ],
                ]);
            }
        };
    }
}
