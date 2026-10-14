<?php

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

beforeEach(function () {
    if ((new ExecutableFinder)->find('node') === null) {
        $this->markTestSkipped('Node.js is not installed; JavaScript contract tests require Node.js.');
    }
});

it('executes the composer and model-selector behavior from the actual widget', function () {
    $process = new Process(['node', '--test', __DIR__.'/../JavaScript/composer-contract.test.cjs']);
    $process->setTimeout(30);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput());
});
