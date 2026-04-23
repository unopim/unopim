<?php

use Webkul\DataTransfer\Contracts\JobTrack as ImportJobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as ImportJobBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

function buildImporterWithColumns(array $columns, array $validColumns): AbstractImporter
{
    $source = new class($columns)
    {
        public function __construct(private array $columns) {}

        public function getColumnNames(): array
        {
            return $this->columns;
        }
    };

    $importer = new class(app(JobTrackBatchRepository::class), $validColumns) extends AbstractImporter
    {
        protected array $permanentAttributes = [];

        public function __construct(JobTrackBatchRepository $repo, private array $validColumns)
        {
            parent::__construct($repo);
            $this->validColumnNames = $validColumns;
        }

        public function validateRow(array $rowData, int $rowNumber): bool
        {
            return true;
        }

        public function importBatch(ImportJobBatchContract $importBatchContract): bool
        {
            return true;
        }

        protected function saveValidatedBatches(): self
        {
            return $this;
        }

        public function errorHelper(): Error
        {
            return $this->errorHelper;
        }
    };

    $import = Mockery::mock(ImportJobTrackContract::class);
    $importer->setImport($import);
    $importer->setSource($source);
    $importer->setErrorHelper(new Error);

    return $importer;
}

it('ignores trailing empty headers when validating spreadsheet columns', function () {
    $columns = array_merge(['locale', 'code', 'name'], array_fill(0, 1000, ''));
    $importer = buildImporterWithColumns($columns, ['locale', 'code', 'name']);

    $importer->validateData();

    $errors = $importer->errorHelper()->getAllErrorsGroupedByCode();

    expect($errors)->not->toHaveKey(AbstractImporter::ERROR_CODE_COLUMN_EMPTY_HEADER);
    expect($errors)->not->toHaveKey(AbstractImporter::ERROR_CODE_INVALID_ATTRIBUTE);
});

it('still reports empty headers that appear between valid ones', function () {
    $columns = ['locale', '', 'code', 'name'];
    $importer = buildImporterWithColumns($columns, ['locale', 'code', 'name']);

    $importer->validateData();

    $errors = $importer->errorHelper()->getAllErrorsGroupedByCode();

    expect($errors)->toHaveKey(AbstractImporter::ERROR_CODE_COLUMN_EMPTY_HEADER);
});
