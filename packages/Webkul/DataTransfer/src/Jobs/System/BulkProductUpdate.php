<?php

namespace Webkul\DataTransfer\Jobs\System;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Services\AttributeService;
use Webkul\DataTransfer\Helpers\AbstractJob;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\ProductValuesValidator;

class BulkProductUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Repository for managing job instances.
     */
    protected JobInstancesRepository $jobInstancesRepository;

    /**
     * Repository for tracking job execution.
     */
    protected JobTrackRepository $jobTrackRepository;

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
     * Collected validation or process errors.
     */
    protected array $errors = [];

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
     *
     * @return void
     */
    public function __construct(
        protected array $updateProducts,
        protected $userId
    ) {}

    /**
     * Handle the bulk product update job.
     *
     * @return void
     */
    public function handle()
    {
        $this->jobInstancesRepository = app(JobInstancesRepository::class);
        $this->jobTrackRepository = app(JobTrackRepository::class);
        $this->attributeService = app(AttributeService::class);
        $this->valuesValidator = app(ProductValuesValidator::class);

        $jobInstance = $this->jobInstancesRepository->findOneByField('code', 'bulk_product_update')
            ?? $this->createDemoJobInstance();

        $this->jobTrackInstance = $this->jobTrackRepository->create([
            'state'            => AbstractJob::STATE_PENDING,
            'meta'             => $jobInstance->toJson(),
            'job_instances_id' => $jobInstance->id,
            'user_id'          => $this->userId,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->jobLogger = JobLogger::make($this->jobTrackInstance->id);
        $productRepository = app(ProductRepository::class);

        try {
            $this->started();

            $formatted = $this->formatData($this->updateProducts, $productRepository);

            $this->validateData($formatted);

            $this->markValidated(count($this->updateProducts));

            $this->saveProducts($this->updateProducts, $productRepository);

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
     * @param  array  $updateProducts  Product updates keyed by product ID.
     * @param  ProductRepository  $productRepository  Repository for fetching and saving products.
     * @return void
     */
    protected function saveProducts(array $updateProducts, ProductRepository $productRepository)
    {
        $processed = 0;
        $productIds = [];

        foreach ($updateProducts as $productId => $attributeData) {
            $product = $productRepository->find($productId);

            if (! $product || ! is_array($attributeData)) {
                continue;
            }

            $productIds[] = $productId;

            $values = $product->values;

            $familyAttributeCodes = $this->getFamilyAttribute($productId, $productRepository);

            foreach ($attributeData as $attributeCode => $value) {
                $attribute = $this->attributeService->findAttributeByCode($attributeCode);

                if (! $attribute) {
                    continue;
                }

                if (! in_array($attributeCode, $familyAttributeCodes, true)) {
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
                        break;
                }
            }

            $product->values = $values;
            $product->save();
            $processed++;

            if ($processed % 10 === 0) {
                $this->updateProgress($processed);
            }
        }

        $this->updateProgress($processed);
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
     * @param  ProductRepository  $productRepository  Repository for fetching product details.
     * @return array Formatted product data grouped by attribute type.
     */
    protected function formatData(array $updateProducts, ProductRepository $productRepository): array
    {
        $formatted = [];

        foreach ($updateProducts as $productId => $attributes) {
            $formatted[$productId] = [
                'common'                  => [],
                'channel_specific'        => [],
                'locale_specific'         => [],
                'channel_locale_specific' => [],
            ];

            $familyAttributeCodes = $this->getFamilyAttribute($productId, $productRepository);

            foreach ($attributes as $attributeCode => $attributeValue) {

                if (! in_array($attributeCode, $familyAttributeCodes, true)) {
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
     *
     * @return void
     */
    public function started()
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
     * @return void
     */
    public function markValidated($count)
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
     *
     * @return void
     */
    public function markCompleted()
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
        ], $this->jobTrackInstance->id);

        $this->jobLogger->info(trans('data_transfer::app.job.completed'));
    }

    /**
     * Update job progress with the number of processed rows.
     *
     * @param  int  $processedCount  Number of processed rows so far.
     * @return void
     */
    public function updateProgress(int $processedCount)
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
     * @param  ProductRepository  $productRepository  Repository to fetch product details.
     * @return array List of attribute codes belonging to the family.
     */
    public function getFamilyAttribute(int $productId, ProductRepository $productRepository)
    {
        $product = $productRepository->find($productId);

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
     * Process price values for common attribute
     */
    protected function processCommonPriceValues(string $field, array $newData, array $oldData): array
    {
        return array_merge($oldData[$field] ?? [], $newData);
    }
}
