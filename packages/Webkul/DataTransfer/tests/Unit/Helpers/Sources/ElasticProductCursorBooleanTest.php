<?php

use Webkul\Core\Facades\ElasticSearch;
use Webkul\DataTransfer\Helpers\Sources\Export\Elastic\ProductCursor;

describe('ElasticProductCursor boolean status filter (Issue #243)', function () {

    beforeEach(function () {
        config([
            'elasticsearch.enabled'                      => true,
            'elasticsearch.prefix'                       => 'testing',
            'elasticsearch.connection'                   => 'default',
            'elasticsearch.connections.default.hosts.0'  => 'testhost:9200',
        ]);

        $elasticClientMock = Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient');

        ElasticSearch::shouldReceive('makeConnection')
            ->andReturn($elasticClientMock);
    });

    it('sends boolean true (not integer 1) when status filter is "enable"', function () {
        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) {
                $boolFilter = $args['body']['query']['bool']['filter'] ?? [];

                // The status filter must exist
                expect($boolFilter)->not->toBeEmpty();

                $statusClause = $boolFilter[0];

                // Should use 'term' or 'terms' with a boolean value
                $statusValue = $statusClause['terms']['status']
                    ?? $statusClause['term']['status']
                    ?? [$statusClause['term']['status'] ?? null];

                $value = is_array($statusValue) ? $statusValue[0] : $statusValue;

                // CRITICAL: The value sent to ES must be boolean true, not integer 1
                expect($value)->toBeBool()
                    ->and($value)->toBeTrue();

                return true;
            })
            ->andReturn([
                'hits' => [
                    'total' => ['value' => 0],
                    'hits'  => [],
                ],
            ]);

        $cursor = new ProductCursor(
            ['filters' => ['status' => 'enable']],
            null,
            100
        );

        $cursor->rewind();
    });

    it('sends boolean false (not integer 0) when status filter is "disable"', function () {
        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) {
                $boolFilter = $args['body']['query']['bool']['filter'] ?? [];

                expect($boolFilter)->not->toBeEmpty();

                $statusClause = $boolFilter[0];

                $statusValue = $statusClause['terms']['status']
                    ?? $statusClause['term']['status']
                    ?? [$statusClause['term']['status'] ?? null];

                $value = is_array($statusValue) ? $statusValue[0] : $statusValue;

                // CRITICAL: The value sent to ES must be boolean false, not integer 0
                expect($value)->toBeBool()
                    ->and($value)->toBeFalse();

                return true;
            })
            ->andReturn([
                'hits' => [
                    'total' => ['value' => 0],
                    'hits'  => [],
                ],
            ]);

        $cursor = new ProductCursor(
            ['filters' => ['status' => 'disable']],
            null,
            100
        );

        $cursor->rewind();
    });

    it('sends no status filter when status is empty', function () {
        ElasticSearch::shouldReceive('search')
            ->once()
            ->withArgs(function ($args) {
                $boolQuery = $args['body']['query']['bool'];

                // When no status filter, bool query should be empty stdClass or have no filter
                if ($boolQuery instanceof stdClass) {
                    return true;
                }

                expect($boolQuery)->not->toHaveKey('filter');

                return true;
            })
            ->andReturn([
                'hits' => [
                    'total' => ['value' => 0],
                    'hits'  => [],
                ],
            ]);

        $cursor = new ProductCursor(
            ['filters' => []],
            null,
            100
        );

        $cursor->rewind();
    });
});
