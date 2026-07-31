<?php

namespace Webkul\Product\Console;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Product\Contracts\VariantValueResolver;
use Webkul\Product\Models\ProductProxy;
use Webkul\Product\Type\AbstractType;

#[Description('Reconcile legacy variants: remove child attribute values that merely duplicate an inherited ancestor value, leaving genuine overrides intact.')]
#[Signature('unopim:variants:strip-redundant
                            {--apply : Delete redundant keys (omit for a dry-run report)}
                            {--product= : Limit to a single configurable product id}')]
class StripRedundantVariantValuesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(VariantValueResolver $resolver): int
    {
        $common = AbstractType::COMMON_VALUES_KEY;

        $query = ProductProxy::modelClass()::query()
            ->where('type', 'configurable')
            ->with('super_attributes');

        if ($id = $this->option('product')) {
            $query->where('id', $id);
        }

        $apply = (bool) $this->option('apply');

        $scanned = 0;
        $stripped = 0;
        $variantCount = 0;

        $query->chunkById(200, function ($parents) use ($resolver, $common, $apply, &$scanned, &$stripped, &$variantCount): void {
            foreach ($parents as $parent) {
                $ancestorCommon = $resolver->resolve($parent)[$common] ?? [];

                $keep = $parent->super_attributes->pluck('code')->push('sku')->all();

                foreach ($parent->variants as $variant) {
                    $variantCount++;

                    $values = $variant->values ?? [];
                    $ownCommon = $values[$common] ?? [];
                    $removed = [];

                    foreach ($ownCommon as $code => $value) {
                        $scanned++;

                        if (in_array($code, $keep, true)) {
                            continue;
                        }

                        if (array_key_exists($code, $ancestorCommon) && $ancestorCommon[$code] === $value) {
                            $removed[] = $code;
                        }
                    }

                    if (empty($removed)) {
                        continue;
                    }

                    $stripped += count($removed);

                    $this->line(sprintf('  %s: %s %s', $variant->sku, $apply ? 'stripped' : 'would strip', implode(', ', $removed)));

                    if ($apply) {
                        foreach ($removed as $code) {
                            unset($ownCommon[$code]);
                        }

                        $values[$common] = $ownCommon;
                        $variant->values = $values;
                        $variant->save();
                    }
                }
            }
        });

        $this->info(sprintf(
            '%s %d redundant value(s) across %d variant(s) (%d keys scanned).',
            $apply ? 'Removed' : 'Would remove',
            $stripped,
            $variantCount,
            $scanned
        ));

        return self::SUCCESS;
    }
}
