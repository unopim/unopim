<?php

use Illuminate\Validation\ValidationException;
use Webkul\DataTransfer\Validators\JobInstances\Export\LocaleJobValidator;

it('accepts the locale export status filter values used by the UI', function (string $status) {
    app(LocaleJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'status'      => $status,
        ],
    ]);
})->with(['enable', 'all'])->throwsNoExceptions();

it('rejects an unknown locale export status filter value', function () {
    app(LocaleJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'status'      => 'archived',
        ],
    ]);
})->throws(ValidationException::class);
