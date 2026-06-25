<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Exporters\Channel\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

it('can prepare channel data for export', function () {
    // Create a channel using factory
    $channel = Channel::factory()->create([
        'code' => 'test-channel',
    ]);

    // Mock dependencies
    $jobTrackBatchRepository = mock(JobTrackBatchRepository::class);
    $exportFileBuffer = mock(FlatItemBuffer::class);
    $channelRepository = app(ChannelRepository::class);

    $exporter = new Exporter($jobTrackBatchRepository, $exportFileBuffer, $channelRepository);

    // Prepare batch data (simulating how results are fetched and stored in batch)
    $batchData = [
        [
            'code'          => $channel->code,
            'root_category' => [
                'code' => $channel->root_category->code,
            ],
            'translations'  => $channel->translations->toArray(),
            'locales'       => $channel->locales->toArray(),
            'currencies'    => $channel->currencies->toArray(),
        ],
    ];

    $batch = new JobTrackBatch(['data' => $batchData]);

    $preparedData = $exporter->prepareChannels($batch);

    expect($preparedData)->toBeArray();
    expect(count($preparedData))->toBeGreaterThanOrEqual(1);

    $row = $preparedData[0];
    expect($row['code'])->toBe('test-channel');
    expect($row['root_category'])->toBe($channel->root_category->code);

    // Check if locales and currencies are correctly imploded
    $locales = explode(',', $row['locales']);
    expect($locales)->toContain($channel->locales->first()->code);

    $currencies = explode(',', $row['currencies']);
    expect($currencies)->toContain($channel->currencies->first()->code);

    // Check translations
    $localesInTranslations = array_column($channel->translations->toArray(), 'locale');
    expect($row['locale'])->toBeIn($localesInTranslations);
});
