<?php

use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\ElasticSearch\Client\Fake\FakeElasticClient;
use Webkul\Product\Models\Product;

beforeEach(function () {
    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    $elasticClientMock = Mockery::mock(FakeElasticClient::class);

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn($elasticClientMock)
        ->zeroOrMoreTimes();
});

it('sends boolean true (not integer 1) when product status is enabled', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->withInitialValues()->create(['status' => 1]);

    $jobTrack = JobTrack::factory()->create();
    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [['sku' => $product->sku]],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) {
            expect($args)->toHaveKey('body');

            $productBody = $args['body'][1] ?? null;

            expect($productBody)->not->toBeNull()
                ->and($productBody['status'])->toBeBool()
                ->and($productBody['status'])->toBeTrue();

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    app(Importer::class)->indexBatch($batch);
});

it('sends boolean false (not integer 0) when product status is disabled', function () {
    config(['elasticsearch.enabled' => false]);

    $product = Product::factory()->withInitialValues()->create(['status' => 0]);

    $jobTrack = JobTrack::factory()->create();
    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [['sku' => $product->sku]],
    ]);

    config(['elasticsearch.enabled' => true]);

    ElasticSearch::shouldReceive('bulk')
        ->once()
        ->withArgs(function (array $args) {
            $productBody = $args['body'][1] ?? null;

            expect($productBody)->not->toBeNull()
                ->and($productBody['status'])->toBeBool()
                ->and($productBody['status'])->toBeFalse();

            return true;
        })
        ->andReturn(['errors' => false, 'items' => []]);

    app(Importer::class)->indexBatch($batch);
});
