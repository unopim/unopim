<?php

namespace Webkul\DataTransfer\Helpers\Exporters\CategoryField;

use Illuminate\Support\Facades\Event;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

class Exporter extends AbstractExporter
{
    /**
     * Create a new instance.
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected CategoryFieldRepository $categoryFieldRepository,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the file buffer for the export process.
     */
    public function initilize(): void
    {
        $this->initializeFileBuffer();
    }

    /**
     * Start the export process.
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();
        $categoryFields = $this->prepareCategoryFields($batch, $filePath);

        $this->exportBuffer->write($categoryFields);

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
     * Prepare category fields from current batch.
     *
     * @return array{code: mixed, type: mixed, locale: mixed, name: mixed, enable_wysiwyg: mixed, section: mixed, position: mixed, status: mixed, is_required: mixed, is_unique: mixed, validation: mixed, regex_pattern: mixed, value_per_locale: mixed}[]
     */
    public function prepareCategoryFields(JobTrackBatchContract $batch, mixed $filePath): array
    {
        $locales = core()->getAllActiveLocales()->pluck('code');
        $categoryFields = [];

        foreach ($batch->data as $rowData) {
            $translations = collect($rowData['translations'] ?? [])->keyBy('locale')->toArray();

            foreach ($locales as $locale) {
                $data = [
                    'code'             => $rowData['code'] ?? null,
                    'type'             => $rowData['type'] ?? null,
                    'locale'           => $locale,
                    'name'             => $translations[$locale]['name'] ?? null,
                    'enable_wysiwyg'   => $rowData['enable_wysiwyg'] ?? null,
                    'section'          => $rowData['section'] ?? null,
                    'position'         => $rowData['position'] ?? null,
                    'status'           => $rowData['status'] ?? null,
                    'is_required'      => $rowData['is_required'] ?? null,
                    'is_unique'        => $rowData['is_unique'] ?? null,
                    'validation'       => $rowData['validation'] ?? null,
                    'regex_pattern'    => $rowData['regex_pattern'] ?? null,
                    'value_per_locale' => $rowData['value_per_locale'] ?? null,
                ];

                $categoryFields[] = $data;
            }

            $this->createdItemsCount++;
        }

        return $categoryFields;
    }
}
