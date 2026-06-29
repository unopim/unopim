<?php

use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Helpers\Importers\Locale\Importer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

beforeEach(function () {
    $this->jobTrack = new class implements JobTrackContract
    {
        public int $id = 77;

        public string $action = ImportHelper::ACTION_APPEND;

        public array $summary = [];

        public object $jobInstance;

        public function __construct()
        {
            $this->jobInstance = new class
            {
                public array $filters = [
                    'status' => 'All',
                ];
            };
        }
    };

    $this->batch = new class implements JobTrackBatchContract
    {
        public int $id = 88;

        public object $jobTrack;

        public array $data = [
            [
                'code'   => 'en_US',
                'status' => 1,
            ],
            [
                'code'   => 'fr_FR',
                'status' => 0,
            ],
        ];

        public function __construct()
        {
            $this->jobTrack = new class
            {
                public string $action = ImportHelper::ACTION_APPEND;
            };
        }
    };

    $this->importBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->localeRepository = Mockery::mock(LocaleRepository::class);

    $this->importer = new Importer(
        $this->importBatchRepository,
        $this->localeRepository
    );

    $this->importer->setImport($this->jobTrack);
});

it('validates a correct locale row', function () {
    $row = ['code' => 'de_DE', 'status' => '1'];

    DB::shouldReceive('table')->with('locales')->andReturnSelf();
    DB::shouldReceive('where')->with('code', 'de_DE')->andReturnSelf();
    DB::shouldReceive('value')->with('id')->andReturn(null);

    $this->importer->setErrorHelper(new Error);

    expect($this->importer->validateRow($row, 1))->toBeTrue();
});

it('fails validation for invalid status', function () {
    $row = ['code' => 'it_IT', 'status' => 'invalid'];

    $this->importer->setErrorHelper(new Error);

    expect($this->importer->validateRow($row, 1))->toBeFalse();
});

it('respects the "Enable" status filter during import', function () {
    $this->jobTrack->jobInstance->filters['status'] = 'enable';

    DB::shouldReceive('table')->with('locales')->andReturnSelf();
    DB::shouldReceive('whereIn')->andReturnSelf();
    DB::shouldReceive('select')->andReturnSelf();
    DB::shouldReceive('get')->andReturn(collect([]));

    $this->localeRepository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn ($data) => $data['code'] === 'en_US'))
        ->andReturn(true);

    $this->localeRepository->shouldNotReceive('update');
    $this->localeRepository->shouldNotReceive('create')->with(Mockery::on(fn ($data) => $data['code'] === 'fr_FR'));

    $this->importBatchRepository->shouldReceive('update')->andReturn(true);

    $this->importer->importBatch($this->batch);

    expect($this->importer->getCreatedItemsCount())->toBe(1);
});

it('imports all locales when filter is "All"', function () {
    $this->jobTrack->jobInstance->filters['status'] = 'All';

    DB::shouldReceive('table')->with('locales')->andReturnSelf();
    DB::shouldReceive('whereIn')->andReturnSelf();
    DB::shouldReceive('select')->andReturnSelf();
    DB::shouldReceive('get')->andReturn(collect([]));

    $this->localeRepository->shouldReceive('create')->twice()->andReturn(true);
    $this->importBatchRepository->shouldReceive('update')->andReturn(true);

    $this->importer->importBatch($this->batch);

    expect($this->importer->getCreatedItemsCount())->toBe(2);
});

afterEach(function () {
    Mockery::close();
});
