<?php

namespace Webkul\DataTransfer\Helpers\Exporters;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Buffer\FileBuffer;
use Webkul\DataTransfer\Contracts\JobTrack as ExportJobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Jobs\Export\Completed as CompletedJob;
use Webkul\DataTransfer\Jobs\Export\ExportBatch as ExportBatchJob;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Jobs\Export\File\SpoutWriterFactory;
use Webkul\DataTransfer\Jobs\Export\UploadFile as UploadFileJob;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

abstract class AbstractExporter
{
    /**
     * Error code for system exception.
     */
    public const ERROR_CODE_SYSTEM_EXCEPTION = 'system_exception';

    /**
     * Error code for column not found.
     */
    public const ERROR_CODE_COLUMN_NOT_FOUND = 'column_not_found';

    /**
     * Error code for column empty header.
     */
    public const ERROR_CODE_COLUMN_EMPTY_HEADER = 'column_empty_header';

    /**
     * Error code for column name invalid.
     */
    public const ERROR_CODE_COLUMN_NAME_INVALID = 'column_name_invalid';

    /**
     * Error code for invalid attribute.
     */
    public const ERROR_CODE_INVALID_ATTRIBUTE = 'invalid_attribute_name';

    /**
     * Error code for wrong quotes.
     */
    public const ERROR_CODE_WRONG_QUOTES = 'wrong_quotes';

    /**
     * Error code for wrong columns number.
     */
    public const ERROR_CODE_COLUMNS_NUMBER = 'wrong_columns_number';

    /**
     * Error message templates.
     */
    protected array $errorMessages = [
        self::ERROR_CODE_SYSTEM_EXCEPTION    => 'data_transfer::app.validation.errors.system',
        self::ERROR_CODE_COLUMN_NOT_FOUND    => 'data_transfer::app.validation.errors.column-not-found',
        self::ERROR_CODE_COLUMN_EMPTY_HEADER => 'data_transfer::app.validation.errors.column-empty-headers',
        self::ERROR_CODE_COLUMN_NAME_INVALID => 'data_transfer::app.validation.errors.column-name-invalid',
        self::ERROR_CODE_INVALID_ATTRIBUTE   => 'data_transfer::app.validation.errors.invalid-attribute',
        self::ERROR_CODE_WRONG_QUOTES        => 'data_transfer::app.validation.errors.wrong-quotes',
        self::ERROR_CODE_COLUMNS_NUMBER      => 'data_transfer::app.validation.errors.column-numbers',
    ];

    public const BATCH_SIZE = 100;

    /**
     * Is linking required
     */
    protected bool $linkingRequired = false;

    /**
     * Is indexing required
     */
    protected bool $indexingRequired = false;

    /**
     * Error helper instance.
     *
     * @var \Webkul\DataTransfer\Helpers\Error
     */
    protected $errorHelper;

    /**
     * export instance.
     */
    protected ExportJobTrackContract $export;

    /**
     * Source instance.
     *
     * @var \Webkul\DataTransfer\Helpers\Source
     */
    protected $source;

    /**
     * Valid column names
     */
    protected array $validColumnNames = [];

    /**
     * Array of numbers of validated rows as keys and boolean TRUE as values
     */
    protected array $validatedRows = [];

    /**
     * Number of rows processed by validation
     */
    protected int $processedRowsCount = 0;

    /**
     * Number of created items
     */
    protected int $createdItemsCount = 0;

    /**
     * Number of updated items
     */
    protected int $updatedItemsCount = 0;

    /**
     * Number of deleted items
     */
    protected int $skipedItemsCount = 0;

    /**
     * Filters
     */
    protected array $filters = [];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer
    ) {}

    /**
     * Validate data row
     */
    // abstract public function validateRow(array $rowData, int $rowNumber): bool;

    /**
     * export data rows
     */
    abstract public function exportBatch(JobTrackBatchContract $exportBatchContract, $filePath): bool;

    /**
     * Initialize Product error messages
     */
    protected function initErrorMessages(): void
    {
        foreach ($this->errorMessages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }
    }

    /**
     * Initializes for the export process.
     *
     * @return void
     */
    public function initilize() {}

    /**
     * export instance.
     */
    public function setExport(ExportJobTrackContract $export): self
    {
        $this->export = $export;

        return $this;
    }

    public function getExportParameter(): array
    {
        return [
            'fieldDelimiter' => $this->export->jobInstance['field_separator'] ?? ',',
            'filedEnclosure' => '"',
            'shouldAddBOM'   => true,
        ];
    }

    /**
     * BufferFile instance.
     */
    public function getBufferFile()
    {
        return $this->exportFileBuffer;
    }

    /**
     * export instance.
     *
     * @param  \Webkul\DataTransfer\Helpers\Source  $errorHelper
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * export instance.
     *
     * @param  \Webkul\DataTransfer\Helpers\Error  $errorHelper
     */
    public function setErrorHelper($errorHelper): self
    {
        $this->errorHelper = $errorHelper;

        $this->initErrorMessages();

        return $this;
    }

    /**
     * export instance.
     *
     * @return \Webkul\DataTransfer\Helpers\Source
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getFilters()
    {
        if (empty($this->filters)) {
            $this->filters = $this->export->jobInstance->filters ?? [];
        }

        return $this->filters;
    }

    protected function getResults()
    {
        return $this->source->all()?->getIterator();
    }

    /**
     * Get filename for generate data
     */
    protected function getFileName(): string
    {
        $fileName = sprintf(
            '%s-%s.%s',
            $this->export->jobInstance->code,
            $this->export->jobInstance->entity_type,
            strtolower($this->filters['file_format'] ?? SpoutWriterFactory::CSV),
        );

        return $fileName;
    }

    /**
     * Start the export process
     */
    public function exportData(?JobTrackBatchContract $exportBatch = null, $filePath = null): bool
    {
        if (! $filePath) {
            $this->filters = $this->export->jobInstance->filters ?? [];
            $directory = sprintf('exports/%s/%s', $this->export->id, FileBuffer::FOLDER_PREFIX);
            $fileName = $this->getFileName();
            $filePath = $this->exportFileBuffer->initilize(
                $directory,
                $this->filters['file_format'] ?? SpoutWriterFactory::CSV,
                $fileName
            );
        }

        if ($exportBatch) {
            $this->exportBatch($exportBatch, $filePath);

            return true;
        }

        $typeBatches = [];

        $chain[] = new UploadFileJob(
            $this->export,
            $filePath->getFilePath(),
            $filePath->getTemporaryPath(),
            $this->filters
        );

        foreach ($this->export->batches as $batch) {
            $typeBatches['export'][] = new ExportBatchJob($batch, $filePath);
        }

        $chain[] = Bus::batch($typeBatches['export']);

        $chain[] = new CompletedJob($this->export);

        Bus::chain($chain)->dispatch();

        return true;
    }

    /**
     * Save validated batches
     */
    public function initializeBatches(): self
    {
        $results = $this->getResults();

        $batchRows = [];

        $results->rewind();

        /**
         * Clean previous saved batches
         */
        $this->exportBatchRepository->deleteWhere([
            'job_track_id' => $this->export->id,
        ]);

        while (
            $results->valid()
            || count($batchRows)
        ) {
            if (
                count($batchRows) == self::BATCH_SIZE
                || ! $results->valid()
            ) {
                $this->exportBatchRepository->create([
                    'job_track_id' => $this->export->id,
                    'data'         => $batchRows,
                ]);

                $batchRows = [];
            }

            if ($results->valid()) {
                $rowData = $results->current();

                $batchRows[] = $rowData;

                $this->processedRowsCount++;

                $results->next();
            }
        }

        $summaryData = [
            'processed' => $this->processedRowsCount,
            'created'   => $this->createdItemsCount,
            'skipped'   => $this->skipedItemsCount,
        ];

        $this->updateSummary($summaryData);

        return $this;
    }

    /**
     * Updates the state of a batch in the export process.
     *
     *
     * @return void
     */
    public function updateBatchState(int $id, string $state)
    {
        $processed = $this->getCreatedItemsCount() - $this->getskippedtemsCount();
        /**
         * Update import batch summary
         */
        $batch = $this->exportBatchRepository->update([
            'state'   => $state,
            'summary' => [
                'processed' => $processed < 0 ? 0 : $processed,
                'created'   => $this->getCreatedItemsCount(),
                'skipped'   => $this->getskippedtemsCount(),
            ],
        ], $id);
    }

    /**
     * Update summary
     */
    public function updateSummary(array $summaryData)
    {
        $summary = $this->export->summary;
        foreach ($summaryData as $key => $value) {
            if (isset($summary[$key])) {
                $summary[$key] += $value;
            } else {
                $summary[$key] = $value;
            }
        }

        $this->export->summary = $summary;
        $this->export->save();
    }

    /**
     * Add row as skipped
     *
     * @param  int|null  $rowNumber
     * @param  string|null  $columnName
     * @param  string|null  $errorMessage
     * @return $this
     */
    protected function skipRow($rowNumber, string $errorCode, $columnName = null, $errorMessage = null): self
    {
        $this->errorHelper->addError(
            $errorCode,
            $rowNumber,
            $columnName,
            $errorMessage
        );

        $this->errorHelper->addRowToSkip($rowNumber);

        $this->skipedItemsCount++;

        return $this;
    }

    /**
     * Returns number of skippef items count
     */
    public function getSkippedtemsCount(): int
    {
        $count = $this->export->summary['skipped'] ?? 0;

        return $count + $this->skipedItemsCount;
    }

    /**
     * Returns number of process rows
     */
    public function getProcessedRowsCount(): int
    {
        $count = $this->export->summary['processed'] ?? 0;

        return $count + $this->processedRowsCount;
    }

    /**
     * Returns number of created items count
     */
    public function getCreatedItemsCount(): int
    {
        $count = $this->export->summary['created'] ?? 0;

        return $count + $this->createdItemsCount;
    }

    /**
     * Returns CSV header based on valid column names.
     */
    public function getCsvHeader(): array
    {
        return $this->validColumnNames;
    }

    /**
     * This function copies a media file from a source path to a destination path.
     * If the source file exists, it will be copied to the destination.
     *
     * @param  string  $sourcePath  The path of the source media file.
     * @param  string  $destinationPath  The path where the media file will be copied.
     * @return void
     */
    public function copyMedia(string $sourcePath, string $destinationPath)
    {
        if (Storage::exists($sourcePath)) {
            // Copy the file
            Storage::copy($sourcePath, $destinationPath);
        }
    }
}
