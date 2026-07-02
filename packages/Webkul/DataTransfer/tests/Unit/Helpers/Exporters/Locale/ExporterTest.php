<?php

use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\JobInstances as JobInstanceContract;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Export as ExportHelper;
use Webkul\DataTransfer\Helpers\Exporters\Locale\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

beforeEach(function () {
    $this->jobTrack = new class implements JobTrackContract
    {
        public int $id = 55;

        public array $summary = [];

        public object $jobInstance;

        public function __construct()
        {
            $this->jobInstance = new class implements JobInstanceContract
            {
                public string $code = 'locale-export';

                public string $entity_type = 'locales';

                public array $filters = [
                    'file_format' => 'Csv',
                ];
            };
        }
    };

    $this->batch = new class implements JobTrackBatchContract
    {
        public int $id = 99;

        public array $data = [
            [
                'id'     => 1,
                'code'   => 'en_US',
                'name'   => 'English',
                'status' => 1,
            ],
            [
                'id'     => 2,
                'code'   => 'fr_FR',
                'name'   => 'French',
                'status' => 0,
            ],
        ];
    };

    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->fileBuffer = Mockery::mock(FlatItemBuffer::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->fileBuffer
    );

    $this->exporter->setExport($this->jobTrack);
});

it('prepares locale rows for export batches', function () {
    $locales = $this->exporter->prepareLocales($this->batch);

    expect($locales)->toBe([
        [
            'id'     => 1,
            'code'   => 'en_US',
            'name'   => 'English',
            'status' => 1,
        ],
        [
            'id'     => 2,
            'code'   => 'fr_FR',
            'name'   => 'French',
            'status' => 0,
        ],
    ]);

    expect($this->exporter->getCreatedItemsCount())->toBe(2);
});

it('writes locale rows to the export buffer and marks the batch as processed', function () {
    Event::fake();

    $this->fileBuffer
        ->shouldReceive('initialize')
        ->once()
        ->withArgs(function (string $directory, string $fileName, array $options): bool {
            return $directory === 'exports/55/uno-pim'
                && $fileName === 'locale-export-locales.csv'
                && $options === ['type' => 'Csv'];
        })
        ->andReturnSelf();

    $this->exportBatchRepository
        ->shouldReceive('update')
        ->once()
        ->with([
            'state'   => ExportHelper::STATE_PROCESSED,
            'summary' => [
                'processed' => 2,
                'created'   => 2,
                'skipped'   => 0,
            ],
        ], 99)
        ->andReturnNull();

    $buffer = new class
    {
        public array $writes = [];

        public function write(array $rows): void
        {
            $this->writes[] = $rows;
        }
    };

    $this->exporter->setExportBuffer($buffer);

    $result = $this->exporter->exportBatch($this->batch, '/tmp/locales.csv');

    expect($result)->toBeTrue();
    expect($buffer->writes)->toHaveCount(1);
    expect($buffer->writes[0])->toBe([
        [
            'id'     => 1,
            'code'   => 'en_US',
            'name'   => 'English',
            'status' => 1,
        ],
        [
            'id'     => 2,
            'code'   => 'fr_FR',
            'name'   => 'French',
            'status' => 0,
        ],
    ]);

    Event::assertDispatched('data_transfer.exports.batch.export.before');
    Event::assertDispatched('data_transfer.exports.batch.export.after');
});

it('returns an empty array and zero count when the batch contains no rows', function () {
    $emptyBatch = new class implements JobTrackBatch
    {
        public int $id = 1;

        public array $data = [];
    };

    $locales = $this->exporter->prepareLocales($emptyBatch);

    expect($locales)->toBe([]);
    expect($this->exporter->getCreatedItemsCount())->toBe(0);
});

it('prepares a single locale row correctly', function () {
    $singleBatch = new class implements JobTrackBatch
    {
        public int $id = 2;

        public array $data = [
            [
                'id'     => 10,
                'code'   => 'de_DE',
                'name'   => 'German',
                'status' => 1,
            ],
        ];
    };

    $locales = $this->exporter->prepareLocales($singleBatch);

    expect($locales)->toHaveCount(1);
    expect($locales[0])->toBe([
        'id'     => 10,
        'code'   => 'de_DE',
        'name'   => 'German',
        'status' => 1,
    ]);
    expect($this->exporter->getCreatedItemsCount())->toBe(1);
});

it('preserves inactive status value (0) without modification', function () {
    $inactiveBatch = new class implements JobTrackBatch
    {
        public int $id = 3;

        public array $data = [
            [
                'id'     => 5,
                'code'   => 'ja_JP',
                'name'   => 'Japanese',
                'status' => 0,
            ],
        ];
    };

    $locales = $this->exporter->prepareLocales($inactiveBatch);

    expect($locales[0]['status'])->toBe(0);
});

it('strips extra source fields and maps only the four expected keys', function () {
    $extraFieldsBatch = new class implements JobTrackBatch
    {
        public int $id = 4;

        public array $data = [
            [
                'id'         => 7,
                'code'       => 'es_ES',
                'name'       => 'Spanish',
                'status'     => 1,
                'created_at' => '2024-01-01',
                'updated_at' => '2024-06-01',
                'extra'      => 'should-be-dropped',
            ],
        ];
    };

    $locales = $this->exporter->prepareLocales($extraFieldsBatch);

    expect($locales[0])->toBe([
        'id'     => 7,
        'code'   => 'es_ES',
        'name'   => 'Spanish',
        'status' => 1,
    ]);
    expect(array_key_exists('created_at', $locales[0]))->toBeFalse();
    expect(array_key_exists('extra', $locales[0]))->toBeFalse();
});

it('accumulates created items count across multiple prepareLocales calls', function () {
    $this->exporter->prepareLocales($this->batch);

    $secondBatch = new class implements JobTrackBatch
    {
        public int $id = 5;

        public array $data = [
            [
                'id'     => 3,
                'code'   => 'pt_BR',
                'name'   => 'Portuguese',
                'status' => 1,
            ],
        ];
    };

    $this->exporter->prepareLocales($secondBatch);

    expect($this->exporter->getCreatedItemsCount())->toBe(3);
});

it('initializes the file buffer with XLS format when job instance specifies Xls', function () {
    $this->jobTrack->jobInstance->filters = ['file_format' => 'Xls'];
    $this->exporter->setExport($this->jobTrack);

    $this->exporter->getFilters();

    $this->fileBuffer
        ->shouldReceive('initialize')
        ->once()
        ->withArgs(function (string $directory, string $fileName, array $options): bool {
            return str_ends_with($fileName, '.xls')
                && $options === ['type' => 'Xls'];
        })
        ->andReturnSelf();

    $this->exporter->initializeFileBuffer();
});

it('dispatches before and after events with the batch as the payload', function () {
    Event::fake();

    $this->fileBuffer->shouldReceive('initialize')->once()->andReturnSelf();
    $this->exportBatchRepository->shouldReceive('update')->once()->andReturnNull();

    $buffer = new class
    {
        public function write(array $rows): void {}
    };

    $this->exporter->setExportBuffer($buffer);
    $this->exporter->exportBatch($this->batch, '/tmp/locales.csv');

    Event::assertDispatched(
        'data_transfer.exports.batch.export.before',
        fn ($event, $payload) => $payload === $this->batch
    );

    Event::assertDispatched(
        'data_transfer.exports.batch.export.after',
        fn ($event, $payload) => $payload === $this->batch
    );
});

it('exports only enabled locales when status filter is set to enable', function () {
    $this->jobTrack->jobInstance->filters = [
        'file_format' => 'Csv',
        'status'      => 'enable',
    ];
    $this->exporter->setExport($this->jobTrack);

    $locales = $this->exporter->prepareLocales($this->batch);

    expect($locales)->toHaveCount(1);
    expect($locales[0]['code'])->toBe('en_US');
    expect($locales[0]['status'])->toBe(1);
    expect($this->exporter->getCreatedItemsCount())->toBe(1);
});

it('skips disabled locales and counts them when status filter is enable', function () {
    $this->jobTrack->jobInstance->filters = [
        'file_format' => 'Csv',
        'status'      => 'enable',
    ];
    $this->exporter->setExport($this->jobTrack);

    $this->exporter->prepareLocales($this->batch);

    expect($this->exporter->getSkippedtemsCount())->toBe(1);
});

it('exports all locales when status filter is set to All', function () {
    $this->jobTrack->jobInstance->filters = [
        'file_format' => 'Csv',
        'status'      => 'All',
    ];
    $this->exporter->setExport($this->jobTrack);

    $locales = $this->exporter->prepareLocales($this->batch);

    expect($locales)->toHaveCount(2);
    expect($this->exporter->getCreatedItemsCount())->toBe(2);
    expect($this->exporter->getSkippedtemsCount())->toBe(0);
});

it('exports all locales when no status filter is provided', function () {
    $locales = $this->exporter->prepareLocales($this->batch);

    expect($locales)->toHaveCount(2);
    expect($this->exporter->getCreatedItemsCount())->toBe(2);
    expect($this->exporter->getSkippedtemsCount())->toBe(0);
});

it('writes only enabled locales to buffer when status filter is enable', function () {
    Event::fake();

    $this->jobTrack->jobInstance->filters = [
        'file_format' => 'Csv',
        'status'      => 'enable',
    ];
    $this->exporter->setExport($this->jobTrack);

    $this->fileBuffer
        ->shouldReceive('initialize')
        ->once()
        ->andReturnSelf();

    $this->exportBatchRepository
        ->shouldReceive('update')
        ->once()
        ->with([
            'state'   => ExportHelper::STATE_PROCESSED,
            'summary' => [
                'processed' => 0,
                'created'   => 1,
                'skipped'   => 1,
            ],
        ], 99)
        ->andReturnNull();

    $buffer = new class
    {
        public array $writes = [];

        public function write(array $rows): void
        {
            $this->writes[] = $rows;
        }
    };

    $this->exporter->setExportBuffer($buffer);

    $result = $this->exporter->exportBatch($this->batch, '/tmp/locales.csv');

    expect($result)->toBeTrue();
    expect($buffer->writes[0])->toHaveCount(1);
    expect($buffer->writes[0][0]['code'])->toBe('en_US');
});
