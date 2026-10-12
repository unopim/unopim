<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Helpers\Exporters\Channel\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

use function Pest\Laravel\mock;

it('can prepare channel data for export', function () {
    $channel = Channel::factory()->create([
        'code' => 'test-channel',
    ]);

    $jobTrackBatchRepository = mock(JobTrackBatchRepository::class);
    $exportFileBuffer = mock(FlatItemBuffer::class);
    $channelRepository = app(ChannelRepository::class);

    $exporter = new Exporter($jobTrackBatchRepository, $exportFileBuffer, $channelRepository);

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

    $locales = explode(',', $row['locales']);
    expect($locales)->toContain($channel->locales->first()->code);

    $currencies = explode(',', $row['currencies']);
    expect($currencies)->toContain($channel->currencies->first()->code);

    $localesInTranslations = array_column($channel->translations->toArray(), 'locale');
    expect($row['locale'])->toBeIn($localesInTranslations);
});
