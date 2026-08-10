<?php

namespace Webkul\DataTransfer\Jobs\System;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Attribute\Services\AttributeService;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\ProductValuesValidator;
use Webkul\User\Models\AdminProxy;

class BulkProductUpdate implements ShouldQueue
{
    use Queueable;

    /**
     * Repository for managing job instances.
     */
    protected JobInstancesRepository $jobInstancesRepository;

    /**
     * Repository for tracking job execution.
     */
    protected JobTrackRepository $jobTrackRepository;

    /**
     * Repository owning the variant-level write guard.
     */
    protected ProductRepository $productRepository;

    /**
     * Service for managing product attributes.
     */
    protected AttributeService $attributeService;

    /**
     * Current job track instance.
     *
     * @var mixed
     */
    protected $jobTrackInstance;

    /**
     * Validator for product attribute values.
     */
    protected ProductValuesValidator $valuesValidator;

    /**
     * Resolves a product's variant structure level and attribute ownership.
     */
    protected VariantStructurePlanner $variantStructurePlanner;

    /**
     * Collected validation or process errors.
     */
    protected array $errors = [];

    /**
     * Non-fatal skip warnings (locked-cell writes rejected server-side).
     */
    protected array $warnings = [];

    /**
     * Cached attribute codes for product families.
     */
    protected array $familyAttributeCache = [];

    /**
     * Logger instance for this job.
     *
     * @var mixed
     */
    protected $jobLogger;

    /**
     * Create a new shouldqueue instance.
     */
    public function __construct(
        protected array $updateProducts,
        protected $userId
    ) {}

    /**
     * Handle the bulk product update job.
     */
    public function handle(): void
    {
        if ($this->userId && ($admin = AdminProxy::find($this->userId))) {
            Auth::login($admin);
        }

        $this->jobInstancesRepository = resolve(JobInstancesRepository::class);
        $this->jobTrackRepository = resolve(JobTrackRepository::class);
        $this->productRepository = resolve(ProductRepository::class);
        $this->attributeService = resolve(AttributeService::class);
        $this->valuesValidator = resolve(ProductValuesValidator::class);
        $this->variantStructurePlanner = resolve(VariantStructurePlanner::class);

        $jobInstance = $this->jobInstancesRepository->findOneByField('code', 'bulk_product_update')
            ?? $this->createDemoJobInstance();

        $this->jobTrackInstance = $this->jobTrackRepository->create([
            'state'               => AbstractJob::STATE_PENDING,
            'type'                => $jobInstance->type,
            'action'              => $jobInstance->action,
            'validation_strategy' => $jobInstance->validation_strategy,
            'allowed_errors'      => $jobInstance->allowed_errors,
            'field_separator'     => $jobInstance->field_separator,
            'file_path'           => $jobInstance->file_path,
            'meta'                => $jobInstance->toArray(),
            'job_instances_id'    => $jobInstance->id,
            'user_id'             => $this->userId,
        ]);

        $this->jobLogger = JobLogger::make($this->jobTrackInstance->id);

        $products = $this->productRepository
            ->with('parent.parent')
            ->findWhereIn('id', array_keys($this->updateProducts))
            ->keyBy('id');

        $this->variantStructurePlanner->primeStructuresFor($products);

        try {
            $this->started();

            $this->normalizeMediaPaths($this->updateProducts, $products);

            $formatted = $this->formatData($this->updateProducts, $products);

            $this->validateData($formatted);

            $this->markValidated(count($this->updateProducts));

            $this->saveProducts($this->updateProducts, $products);

            $this->markCompleted();
        } catch (\Exception $e) {
            $this->jobLogger->error('Job failed: '.$e->getMessage());
            $this->jobTrackRepository->update([
                'state'  => AbstractJob::STATE_FAILED,
                'errors' => [$e->getMessage()],
            ], $this->jobTrackInstance->id);
        }
    }

    /**
     * Save updated attribute values for products.
     *
     * One bulk event carries every processed id, so listeners run once rather than
     * per product. Its payload is wrapped in a named key because `call_user_func_array`
     * would otherwise spread the ids as separate arguments.
     *
     * @param  array  $updateProducts  Product updates keyed by product ID.
     * @param  Collection<int, Product>  $products  Preloaded products, keyed by id, with their ancestor chain eager loaded.
     * @return void
     */
    protected function saveProducts(array $updateProducts, $products)
    {
        $processed = 0;
        $productIds = [];

        foreach ($updateProducts as $productId => $attributeData) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }
            if (! is_array($attributeData)) {
                continue;
            }

            $values = $product->values;

            $submittedCommon = [];

            $familyAttributeCodes = $this->getFamilyAttribute($productId, $products);

            foreach ($attributeData as $attributeCode => $value) {
                $attribute = $this->attributeService->findAttributeByCode($attributeCode);

                if (! $attribute instanceof Attribute) {
                    continue;
                }

                if (! in_array($attributeCode, $familyAttributeCodes, true)) {
                    continue;
                }

                if ($this->isBulkEditRestricted($product, $attributeCode)) {
                    $this->warnings[] = "Product ID {$productId} - {$attributeCode}: not editable at this product's variant level, skipped.";

                    continue;
                }

                $type = match (true) {
                    $attribute->isLocaleAndChannelBasedAttribute() => 'locale_channel',
                    $attribute->isChannelBasedAttribute()          => 'channel',
                    $attribute->isLocaleBasedAttribute()           => 'locale',
                    default                                        => 'default',
                };

                switch ($type) {
                    case 'locale_channel':
                        foreach ($value as $channel => $locales) {
                            foreach ($locales as $locale => $val) {
                                $attribute->setProductValue($val, $values, $channel, $locale);
                            }
                        }
                        break;

                    case 'channel':
                        foreach ($value as $channel => $val) {
                            $attribute->setProductValue($val, $values, $channel);
                        }
                        break;

                    case 'locale':
                        foreach ($value as $locale => $val) {
                            $attribute->setProductValue($val, $values, null, $locale);
                        }
                        break;

                    case 'default':
                        if ($attribute->code === 'sku') {
                            if (! empty($value)) {
                                $product->sku = $value;
                            }

                            break;
                        }

                        if ($attribute->type === 'price') {
                            $value = $this->processCommonPriceValues($attributeCode, $value, $values['common'] ?? []);
                        }

                        $attribute->setProductValue($value, $values);

                        $submittedCommon[$attributeCode] = $value;
                        break;
                }
            }

            if (! $this->persistProduct($product, $values, $submittedCommon)) {
                continue;
            }

            $productIds[] = $productId;

            $processed++;

            if ($processed % 10 === 0) {
                $this->updateProgress($processed);
            }
        }

        if ($productIds !== []) {
            Event::dispatch('catalog.product.bulk.edit.after', ['ids' => $productIds]);
        }

        $this->updateProgress($processed);
    }

    /**
     * Persist a product's rebuilt value set through
     * {@see ProductRepository::guardVariantLevelWrite()}. Values are assigned only
     * inside the persist closure because the guard compares the submission against
     * the product's stored values. A refusal warns and the batch carries on.
     *
     * @param  array<string, mixed>|null  $values  Rebuilt product value set, ready to assign; null for a product that has none.
     * @param  array<string, mixed>  $submittedCommon  Bulk-edited common values, keyed by attribute code.
     * @return bool Whether the product passed the guard.
     */
    protected function persistProduct(Product $product, ?array $values, array $submittedCommon): bool
    {
        $persist = function () use ($product, $values): void {
            $product->values = $values;

            if (! $product->isDirty()) {
                return;
            }

            $product->save();

            Event::dispatch('catalog.product.update.after', [$product, true]);
        };

        if (! $product->parent_id || $submittedCommon === []) {
            $persist();

            return true;
        }

        try {
            $this->productRepository->guardVariantLevelWrite(
                $product,
                $submittedCommon,
                $persist,
                $this->variantStructurePlanner
            );
        } catch (ValidationException $e) {
            foreach (Arr::flatten($e->errors()) as $message) {
                $this->warnings[] = "Product ID {$product->id}: {$message}";
            }

            return false;
        }

        return true;
    }

    protected function normalizeMediaPaths(array &$updateProducts, $products): void
    {
        $mediaTypes = [
            AttributeTypes::IMAGE_ATTRIBUTE_TYPE,
            AttributeTypes::FILE_ATTRIBUTE_TYPE,
            AttributeTypes::GALLERY_ATTRIBUTE_TYPE,
        ];

        foreach ($updateProducts as $productId => &$attributeData) {
            if (! is_array($attributeData) || ! $products->has($productId)) {
                continue;
            }

            foreach ($attributeData as $attributeCode => &$value) {
                $attribute = $this->attributeService->findAttributeByCode($attributeCode);

                if (! $attribute instanceof Attribute || ! in_array($attribute->type, $mediaTypes, true)) {
                    continue;
                }

                $value = $this->normalizeMediaValue(
                    $value,
                    (int) $productId,
                    $attributeCode,
                    $attribute->type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE
                );
            }
        }

        unset($attributeData, $value);
    }

    protected function normalizeMediaValue(mixed $value, int $productId, string $attributeCode, bool $isMultiple): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeMediaValue($item, $productId, $attributeCode, $isMultiple);
            }

            return $value;
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        $prefix = 'product/'.$productId.'/'.$attributeCode.'/';

        $paths = $isMultiple
            ? array_filter(array_map('trim', explode(',', $value)))
            : [$value];

        $normalized = array_map(
            fn (string $path): string => str_starts_with($path, $prefix)
                ? $path
                : $this->copyMediaToProduct($path, $prefix),
            $paths
        );

        return implode(',', array_filter($normalized));
    }

    protected function copyMediaToProduct(string $sourcePath, string $targetPrefix): string
    {
        if (! Storage::exists($sourcePath)) {
            return $sourcePath;
        }

        $targetPath = $targetPrefix.pathinfo($sourcePath, PATHINFO_BASENAME);

        Storage::put($targetPath, Storage::get($sourcePath));

        return $targetPath;
    }

    /**
     * Validate prepared product attribute values.
     *
     * @param  array  $preparedProducts  Prepared product values keyed by product ID.
     */
    public function validateData(array $preparedProducts): void
    {
        foreach ($preparedProducts as $productId => $values) {
            try {

                $this->valuesValidator->validate(data: $values, productId: $productId);

            } catch (ValidationException $e) {
                foreach ($e->validator->errors()->messages() as $key => $message) {
                    $messageKey = str_replace('.', '][', $key);
                    $formattedKey = 'values['.$messageKey.']';

                    $this->jobLogger->error("Product ID {$productId} - {$formattedKey}: ".implode(', ', $message));
                    unset($this->updateProducts[$productId]);
                }
            }
        }
    }

    /**
     * Format raw product update data into structured groups.
     *
     * @param  array  $updateProducts  Product updates keyed by product ID.
     * @param  Collection<int, Product>  $products  Preloaded products, keyed by id, with their ancestor chain eager loaded.
     * @return array Formatted product data grouped by attribute type.
     */
    protected function formatData(array $updateProducts, $products): array
    {
        $formatted = [];

        foreach ($updateProducts as $productId => $attributes) {
            $formatted[$productId] = [
                'common'                  => [],
                'channel_specific'        => [],
                'locale_specific'         => [],
                'channel_locale_specific' => [],
            ];

            $product = $products->get($productId);

            $familyAttributeCodes = $this->getFamilyAttribute($productId, $products);

            foreach ($attributes as $attributeCode => $attributeValue) {

                if (! in_array($attributeCode, $familyAttributeCodes, true)) {
                    continue;
                }

                if ($product && $this->isBulkEditRestricted($product, $attributeCode)) {
                    continue;
                }

                $attribute = $this->attributeService->findAttributeByCode($attributeCode);

                $type = match (true) {
                    $attribute->isLocaleAndChannelBasedAttribute() => 'channel_locale_specific',
                    $attribute->isChannelBasedAttribute()          => 'channel_specific',
                    $attribute->isLocaleBasedAttribute()           => 'locale_specific',
                    default                                        => 'common',
                };

                switch ($type) {
                    case 'channel_locale_specific':
                        foreach ($attributeValue as $channel => $locales) {
                            foreach ($locales as $locale => $value) {
                                $formatted[$productId]['channel_locale_specific'][$channel][$locale][$attributeCode] = $value;
                            }
                        }
                        break;

                    case 'channel_specific':
                        foreach ($attributeValue as $channel => $value) {
                            $formatted[$productId]['channel_specific'][$channel][$attributeCode] = $value;
                        }
                        break;

                    case 'locale_specific':
                        foreach ($attributeValue as $locale => $value) {
                            $formatted[$productId]['locale_specific'][$locale][$attributeCode] = $value;
                        }
                        break;

                    case 'common':
                        $formatted[$productId]['common'][$attributeCode] = $attributeValue;
                        break;
                }
            }
        }

        return $formatted;
    }

    /**
     * Mark the job as started and update its state.
     */
    public function started(): void
    {
        $this->jobLogger->info(trans('data_transfer::app.job.started'));

        $this->jobTrackRepository->update([
            'state'      => AbstractJob::STATE_PROCESSING,
            'started_at' => now(),
            'summary'    => [],
        ], $this->jobTrackInstance->id);
    }

    /**
     * Mark the job as validated and update summary counts.
     *
     * @param  int  $count  Number of successfully validated products.
     */
    public function markValidated($count): void
    {
        $this->jobTrackRepository->update([
            'state'              => AbstractJob::STATE_VALIDATED,
            'invalid_rows_count' => count($this->updateProducts) - $count,
            'summary'            => [
                'total_rows_count' => count($this->updateProducts),
            ],
        ], $this->jobTrackInstance->id);
    }

    /**
     * Mark the job as completed and update summary details.
     */
    public function markCompleted(): void
    {
        $this->jobTrackInstance->refresh();

        $summary = [
            'updated'   => $this->jobTrackInstance->processed_rows_count,
            'created'   => 0,
            'skipped'   => $this->jobTrackInstance->invalid_rows_count,
        ];

        $this->jobTrackRepository->update([
            'state'        => AbstractJob::STATE_COMPLETED,
            'summary'      => $summary,
            'completed_at' => now(),
            'errors'       => $this->warnings,
        ], $this->jobTrackInstance->id);

        $this->jobLogger->info(trans('data_transfer::app.job.completed'));
    }

    /**
     * Update job progress with the number of processed rows.
     *
     * @param  int  $processedCount  Number of processed rows so far.
     */
    public function updateProgress(int $processedCount): void
    {
        $this->jobTrackRepository->update([
            'state'                => AbstractJob::STATE_PROCESSING,
            'processed_rows_count' => $processedCount,
        ], $this->jobTrackInstance->id);
    }

    /**
     * Create a demo job instance for bulk product update.
     *
     * @return mixed The created job instance.
     */
    public function createDemoJobInstance()
    {
        return $this->jobInstancesRepository->create([
            'type'                  => 'system',
            'action'                => 'update',
            'code'                  => 'bulk_product_update',
            'entity_type'           => 'products',
            'validation_strategy'   => 'strict',
            'allowed_errors'        => 0,
            'field_separator'       => ',',
            'file_path'             => '',
            'images_directory_path' => '',
            'filters'               => '',
        ]);
    }

    /**
     * Get attribute codes for the product's family.
     *
     * @param  int  $productId  ID of the product.
     * @param  Collection<int, Product>  $products  Preloaded products, keyed by id.
     * @return array List of attribute codes belonging to the family.
     */
    public function getFamilyAttribute(int $productId, $products)
    {
        $product = $products->get($productId);

        if (! $product) {
            return [];
        }
        $familyId = $product->attribute_family_id;

        if (! isset($this->familyAttributeCache[$familyId])) {
            $productFamily = $product->attribute_family;

            $familyAttributeCodes = $productFamily?->custom_attributes->pluck('code')->toArray() ?? [];

            $familyAttributeCodes[] = 'sku';

            $this->familyAttributeCache[$familyId] = $familyAttributeCodes;
        }

        return $this->familyAttributeCache[$familyId];
    }

    /**
     * Ownership screen for a bulk-edited attribute: one maintained above or below this
     * product's own level is skipped, so an ancestor's value is never materialised onto
     * the node. Own-level attributes are left to the write guard, which reads the same
     * {@see \Webkul\Product\Services\VariantStructurePlanner}, so the two cannot drift.
     */
    protected function isBulkEditRestricted(Product $product, string $attributeCode): bool
    {
        if ($attributeCode === 'sku') {
            return false;
        }

        return ! $this->variantStructurePlanner->ownsAtOwnLevel($product, $attributeCode);
    }

    /**
     * Process price values for common attribute
     */
    protected function processCommonPriceValues(string $field, array $newData, array $oldData): array
    {
        return array_merge($oldData[$field] ?? [], $newData);
    }
}
