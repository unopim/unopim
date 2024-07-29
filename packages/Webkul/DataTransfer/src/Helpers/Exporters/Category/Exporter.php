<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Category;

use Illuminate\Support\Facades\Event;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    /**
     * @var array
     */
    protected $categoryFields = [];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected CategoryFieldRepository $categoryFieldRepository,
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
        $this->categoryFields = $this->categoryFieldRepository->getActiveCategoryFields();
    }

    /**
     * Start the import process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();
        $categories = $this->prepareCategories($batch, $filePath);

        $this->exportFileBuffer->addData($categories, $filePath, $this->getExportParameter());

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
        return $this->source->with('parent_category')->all()?->getIterator();
    }

    /**
     * Prepare categories from current batch
     */
    public function prepareCategories(JobTrackBatchContract $batch, mixed $filePath)
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $categories = [];
        foreach ($batch->data as $rowData) {
            foreach ($locales as $locale) {
                $commonFields = $this->getCommonFields($rowData);
                $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $locale);
                // Merge common and locale-specific fields before array_merge
                $mergedFields = array_merge($commonFields, $localeSpecificFields);
                $additionalData = $this->setFieldsAdditionalData($mergedFields, $filePath);

                $data = array_merge([
                    'locale' => $locale,
                    'code'   => $rowData['code'],
                    'parent' => $rowData['parent_category']['code'] ?? null,
                ], $additionalData);

                $categories[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $categories;
    }

    /**
     * Sets category field values for a product. If an category field is not present in the given values array,
     *
     * @param  array  $values
     * @return array
     */
    protected function setFieldsAdditionalData(array $additionalData, $filePath, $options = [])
    {
        $filters = $this->getFilters();
        $withMedia = (bool) $filters['with_media'];

        foreach ($this->categoryFields as $key => $field) {
            if (! isset($additionalData[$field->code])) {
                $additionalData[$field->code] = null;
            }

            if ($withMedia && in_array($field->type, [FieldValidator::FILE_FIELD_TYPE, FieldValidator::IMAGE_FIELD_TYPE])) {
                $exitingFilePath = $additionalData[$field->code] ?? null;
                if ($exitingFilePath && ! empty($exitingFilePath)) {
                    $oldFilePath = $this->removeLastSegment($exitingFilePath);
                    $newfilePath = $filePath->getTemporaryPath().'/'.$oldFilePath;
                    $this->copyMedia($exitingFilePath, $newfilePath);
                }
            }
        }

        return $additionalData;
    }

    /**
     * Retrieves common fields from the given data array.
     *
     *
     * @return array
     */
    private function getCommonFields(array $data)
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('additional_data', $data) || ! array_key_exists('common', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['common'];
    }

    /**
     * Retrieves locale-specific fields from the given data array.
     *
     * @param  string  $locale
     * @return array
     */
    private function getLocaleSpecificFields(array $data, $locale)
    {
        if (! is_array($data['additional_data'])) {
            return [];
        }

        if (! array_key_exists('additional_data', $data) || ! array_key_exists('locale_specific', $data['additional_data'])) {
            return [];
        }

        return $data['additional_data']['locale_specific'][$locale] ?? [];
    }
}
