<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Attribute;

use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Models\ProductProxy;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected AttributeRepository $attributeRepository,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the channels and locales for the export process.
     *
     * @return void
     */
    public function initilize()
    {
        $this->initializeFileBuffer();
    }

    /**
     * Start the export process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();
        $attributes = $this->prepareAttributes($batch, $filePath);

        $this->exportBuffer->write($attributes);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        return $this->source->all()?->getIterator();
    }

    /**
     * Prepare attributes from current batch
     */
    public function prepareAttributes(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $attributes = [];

        foreach ($batch->data as $rowData) {
            $productCounts = $this->productCountsByAttribute($rowData['code']);
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale');

            foreach ($locales as $locale) {
                $data = [
                    'code'              => $rowData['code'] ?? null,
                    'type'              => $rowData['type'] ?? null,
                    'locale'            => $locale,
                    'name'              => $translations[$locale]['name'] ?? null,
                    'position'          => $rowData['position'] ?? null,
                    'enable_wysiwyg'    => $rowData['enable_wysiwyg'] ?? null,
                    'swatch_type'       => $rowData['swatch_type'] ?? null,
                    'is_required'       => $rowData['is_required'] ?? null,
                    'is_unique'         => $rowData['is_unique'] ?? null,
                    'validation'        => $rowData['validation'] ?? null,
                    'regex_pattern'     => $rowData['regex_pattern'] ?? null,
                    'value_per_locale'  => $rowData['value_per_locale'] ?? null,
                    'value_per_channel' => $rowData['value_per_channel'] ?? null,
                    'is_filterable'     => $rowData['is_filterable'] ?? null,
                    'ai_translate'      => $rowData['ai_translate'] ?? null,
                    'productCounts'     => $productCounts,
                ];

                $attributes[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $attributes;
    }

    /**
     * get product count the given attribute code
     */
    protected function productCountsByAttribute(string $code): int
    {
        return ProductProxy::query()
            ->whereNotNull('values->'.$code)
            ->count();
    }
}
