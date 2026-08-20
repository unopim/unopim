<?php

use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\JobLogger;
use Webkul\User\Models\Admin;

class FilelessTestSource extends AbstractSource
{
    protected int $position = 0;

    public function __construct(protected array $rows = [])
    {
        $first = reset($this->rows);

        $this->columnNames = is_array($first) ? array_keys($first) : [];

        $this->totalColumns = count($this->columnNames);

        array_unshift($this->rows, $this->columnNames);
    }

    protected function getNextRow(): array|bool
    {
        if (! isset($this->rows[$this->position])) {
            return false;
        }

        return $this->rows[$this->position++];
    }

    public function rewind(): void
    {
        $this->position = 0;

        parent::rewind();
    }
}

class FilelessTestImporter extends AbstractImporter
{
    public static array $rows = [];

    protected array $validColumnNames = ['code', 'status'];

    protected array $permanentAttributes = ['code'];

    protected string $masterAttributeCode = 'code';

    public function getSource(): AbstractSource
    {
        if (! $this->source) {
            $this->source = new FilelessTestSource(self::$rows);
        }

        return $this->source;
    }

    public function validateRow(array $rowData, int $rowNumber): bool
    {
        $this->validatedRows[$rowNumber] = true;

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    public function importBatch(JobTrackBatchContract $batch): bool
    {
        $this->importBatchRepository->update([
            'state'   => Import::STATE_PROCESSED,
            'summary' => ['created' => 0, 'updated' => count($batch->data), 'deleted' => 0],
        ], $batch->id);

        return true;
    }
}

function registerFilelessImporter(array $rows, bool $hasFileOptions = false): void
{
    FilelessTestImporter::$rows = $rows;

    $entry = ['title' => 'Fileless Test', 'importer' => FilelessTestImporter::class];

    if ($hasFileOptions) {
        $entry['has_file_options'] = true;
    }

    config(['importers.fileless_test' => $entry]);
}

function makeFilelessJobInstance(?string $filePath = null): JobInstances
{
    return JobInstances::create([
        'code'                => 'fileless-test-'.uniqid(),
        'entity_type'         => 'fileless_test',
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 0,
        'file_path'           => $filePath,
    ]);
}

function runFilelessValidation(JobInstances $jobInstance): JobTrack
{
    $track = JobTrack::create([
        'type'                => 'import',
        'state'               => Import::STATE_PENDING,
        'action'              => $jobInstance->action,
        'validation_strategy' => $jobInstance->validation_strategy,
        'allowed_errors'      => $jobInstance->allowed_errors,
        'file_path'           => $jobInstance->file_path,
        'meta'                => $jobInstance->toArray(),
        'job_instances_id'    => $jobInstance->id,
        'user_id'             => Admin::value('id'),
    ]);

    $helper = resolve(Import::class);
    $helper->setImport($track);
    $helper->setLogger(JobLogger::make($track->id));
    $helper->validate();

    return $track->fresh();
}

it('validates a fileless import without an uploaded file', function () {
    registerFilelessImporter([
        ['code' => 'alpha', 'status' => '1'],
        ['code' => 'beta', 'status' => '0'],
        ['code' => 'gamma', 'status' => '1'],
    ]);

    $track = runFilelessValidation(makeFilelessJobInstance());

    expect($track->state)->toBe(Import::STATE_VALIDATED)
        ->and($track->processed_rows_count)->toBe(3)
        ->and($track->errors_count)->toBe(0);
});

it('fails a file based import when the file is missing', function () {
    registerFilelessImporter([['code' => 'alpha', 'status' => '1']], hasFileOptions: true);

    $track = runFilelessValidation(makeFilelessJobInstance());

    expect($track->state)->toBe(Import::STATE_FAILED);
});
