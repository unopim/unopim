<?php

use Webkul\DataTransfer\Helpers\Importers\Product\Importer;

beforeEach(function (): void {
    if (! function_exists('pcntl_wifexited')) {
        $this->markTestSkipped('Requires the pcntl extension.');
    }
});

/**
 * Write a worker result payload and return what the importer makes of it.
 */
function workerResultFor(?int $exitStatus, array|string|null $payload, int $expectedRows): ?array
{
    $resultFile = sys_get_temp_dir().'/parallel-validation-worker-'.uniqid().'.json';

    if ($payload !== null) {
        file_put_contents($resultFile, is_string($payload) ? $payload : json_encode($payload));
    }

    $importer = app(Importer::class);

    try {
        return (new ReflectionMethod($importer, 'readWorkerResult'))->invoke($importer, $resultFile, $exitStatus, $expectedRows);
    } finally {
        @unlink($resultFile);
    }
}

/**
 * Build the wait status a child that exited normally with the given code reports.
 */
function exitedWithCode(int $code): int
{
    return $code << 8;
}

it('trusts a worker that exited cleanly and reported its whole slice', function (): void {
    $result = workerResultFor(exitedWithCode(0), ['invalid' => [10, 20], 'processed' => 1250, 'visited' => 1250], 1250);

    expect($result['invalid'])->toBe([10, 20]);
});

it('rejects a worker that was killed even when it left a result file', function (): void {
    expect(workerResultFor(SIGKILL, ['invalid' => [], 'processed' => 1250, 'visited' => 1250], 1250))->toBeNull();
});

it('rejects a worker that exited with an error code', function (): void {
    expect(workerResultFor(exitedWithCode(1), ['invalid' => [], 'processed' => 0, 'visited' => 0], 1250))->toBeNull();
});

it('rejects a worker whose exit status was never collected', function (): void {
    expect(workerResultFor(null, ['invalid' => [], 'processed' => 1250, 'visited' => 1250], 1250))->toBeNull();
});

it('rejects a worker that left no result file', function (): void {
    expect(workerResultFor(exitedWithCode(0), null, 1250))->toBeNull();
});

it('rejects a worker whose result file is not valid JSON', function (): void {
    expect(workerResultFor(exitedWithCode(0), '{"invalid": [', 1250))->toBeNull();
});

it('rejects a worker that read fewer rows than its slice holds', function (): void {
    expect(workerResultFor(exitedWithCode(0), ['invalid' => [], 'processed' => 78, 'visited' => 78], 1250))->toBeNull();
});

it('rejects a result written by a worker from before the visited count existed', function (): void {
    expect(workerResultFor(exitedWithCode(0), ['invalid' => [], 'processed' => 1250], 1250))->toBeNull();
});
