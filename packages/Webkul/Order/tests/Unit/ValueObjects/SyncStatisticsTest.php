<?php

use Webkul\Order\ValueObjects\SyncStatistics;

it('can create sync statistics', function () {
    $stats = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 120.5
    );

    expect($stats->totalSyncs)->toBe(100)
        ->and($stats->successfulSyncs)->toBe(85)
        ->and($stats->failedSyncs)->toBe(15)
        ->and($stats->averageDuration)->toBe(120.5);
});

it('calculates success rate correctly', function () {
    $stats = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 120.5
    );

    expect($stats->getSuccessRate())->toBe(85.0);
});

it('calculates failure rate correctly', function () {
    $stats = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 120.5
    );

    expect($stats->getFailureRate())->toBe(15.0);
});

it('handles zero syncs correctly', function () {
    $stats = new SyncStatistics(
        totalSyncs: 0,
        successfulSyncs: 0,
        failedSyncs: 0,
        averageDuration: 0
    );

    expect($stats->getSuccessRate())->toBe(0.0)
        ->and($stats->getFailureRate())->toBe(0.0);
});

it('formats average duration as human readable', function () {
    $stats = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 125.0
    );

    expect($stats->getFormattedAverageDuration())->toBe('2m 5s');
});

it('converts to array correctly', function () {
    $stats = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 120.5
    );

    $array = $stats->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['total_syncs', 'successful_syncs', 'failed_syncs', 'average_duration'])
        ->and($array['success_rate'])->toBe(85.0);
});

it('determines health status based on success rate', function () {
    $excellent = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 98,
        failedSyncs: 2,
        averageDuration: 120.0
    );

    $good = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 85,
        failedSyncs: 15,
        averageDuration: 120.0
    );

    $poor = new SyncStatistics(
        totalSyncs: 100,
        successfulSyncs: 60,
        failedSyncs: 40,
        averageDuration: 120.0
    );

    expect($excellent->getHealthStatus())->toBe('excellent')
        ->and($good->getHealthStatus())->toBe('good')
        ->and($poor->getHealthStatus())->toBe('poor');
});
