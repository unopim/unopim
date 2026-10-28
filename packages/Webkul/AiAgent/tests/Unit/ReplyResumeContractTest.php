<?php

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

beforeEach(function () {
    if ((new ExecutableFinder)->find('node') === null) {
        $this->markTestSkipped('Node.js is not installed; JavaScript contract tests require Node.js.');
    }
});

it('restricts chat images and stops reply polling after navigation in the actual widget', function () {
    $process = new Process(['node', '--test', __DIR__.'/../JavaScript/reply-resume.test.cjs']);
    $process->setTimeout(30);
    $process->run();

    expect($process->getExitCode())->toBe(0, $process->getOutput().$process->getErrorOutput());
});
