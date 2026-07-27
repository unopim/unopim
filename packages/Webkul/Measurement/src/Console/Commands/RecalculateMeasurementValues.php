<?php

namespace Webkul\Measurement\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Product\Repositories\ProductRepository;

#[Description('Rebuild the stored base value of every product measurement from the current family definitions')]
#[Signature('measurement:recalculate
        {--family= : Only recalculate values belonging to this measurement family code}
        {--chunk=200 : Number of products loaded per batch}
        {--dry-run : Report what would change without writing}')]
class RecalculateMeasurementValues extends Command
{
    /**
     * Attribute codes of every measurement attribute, keyed by code.
     */
    protected array $measurementAttributes = [];

    /**
     * Execute the console command.
     */
    public function handle(
        ProductRepository $productRepository,
        AttributeRepository $attributeRepository,
        AttributeMeasurementRepository $attributeMeasurementRepository,
        MeasurementHelper $helper
    ): int {
        $familyFilter = $this->option('family');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        foreach ($attributeRepository->findWhere(['type' => 'measurement']) as $attribute) {
            $measurement = $attributeMeasurementRepository->getByAttributeId($attribute->id);
            if (! $measurement) {
                continue;
            }
            if (! $measurement->family) {
                continue;
            }

            if ($familyFilter && $measurement->family_code !== $familyFilter) {
                continue;
            }

            $this->measurementAttributes[$attribute->code] = $attribute;
        }

        if ($this->measurementAttributes === []) {
            $this->components->warn('No configured measurement attributes found — nothing to recalculate.');

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Recalculating %d measurement attribute(s)%s.',
            count($this->measurementAttributes),
            $dryRun ? ' (dry run)' : ''
        ));

        $scanned = 0;
        $changed = 0;
        $valuesChanged = 0;

        $productRepository->getModel()->newQuery()
            ->select('id', 'sku', 'values')
            ->chunkById($chunk, function ($products) use (&$scanned, &$changed, &$valuesChanged, $helper, $dryRun): void {
                foreach ($products as $product) {
                    $scanned++;

                    $values = is_string($product->values)
                        ? json_decode($product->values, true) ?? []
                        : ($product->values ?? []);

                    if (empty($values)) {
                        continue;
                    }

                    $touched = $this->rebuildValues($values, $helper);

                    if ($touched === 0) {
                        continue;
                    }

                    $changed++;
                    $valuesChanged += $touched;

                    if ($dryRun) {
                        $this->line(sprintf('  <comment>would update</comment> %s (%d value(s))', $product->sku, $touched));

                        continue;
                    }

                    $product->values = $values;
                    $product->saveQuietly();
                }
            });

        $this->newLine();
        $this->components->info(sprintf(
            '%s %d product(s) of %d scanned — %d measurement value(s) %s.',
            $dryRun ? 'Would update' : 'Updated',
            $changed,
            $scanned,
            $valuesChanged,
            $dryRun ? 'out of date' : 'rebuilt'
        ));

        return self::SUCCESS;
    }

    /**
     * Walk every scope of a product's values and rebuild the measurement entries.
     */
    protected function rebuildValues(array &$values, MeasurementHelper $helper): int
    {
        $touched = 0;

        foreach ($values as $scope => &$scopedValues) {
            if (! is_array($scopedValues)) {
                continue;
            }

            if ($scope === 'common') {
                $touched += $this->rebuildScope($scopedValues, $helper);

                continue;
            }

            if ($scope === 'locale_specific' || $scope === 'channel_specific') {
                foreach ($scopedValues as &$inner) {
                    if (is_array($inner)) {
                        $touched += $this->rebuildScope($inner, $helper);
                    }
                }

                continue;
            }

            if ($scope === 'channel_locale_specific') {
                foreach ($scopedValues as &$channelValues) {
                    if (! is_array($channelValues)) {
                        continue;
                    }

                    foreach ($channelValues as &$localeValues) {
                        if (is_array($localeValues)) {
                            $touched += $this->rebuildScope($localeValues, $helper);
                        }
                    }
                }
            }
        }

        return $touched;
    }

    /**
     * Rebuild every measurement value held in a single scope.
     */
    protected function rebuildScope(array &$scopedValues, MeasurementHelper $helper): int
    {
        $touched = 0;

        foreach ($scopedValues as $attributeCode => $value) {
            $attribute = $this->measurementAttributes[$attributeCode] ?? null;
            if (! $attribute) {
                continue;
            }
            if (! is_array($value)) {
                continue;
            }
            if (! isset($value['unit'])) {
                continue;
            }

            $amount = $value['amount'] ?? $value['value'] ?? null;
            if ($amount === null) {
                continue;
            }
            if ($amount === '') {
                continue;
            }

            $rebuilt = $helper->getMeasurementValueStructure($amount, $value['unit'], $attribute);

            if ($rebuilt == $value) {
                continue;
            }

            $scopedValues[$attributeCode] = $rebuilt;
            $touched++;
        }

        return $touched;
    }
}
