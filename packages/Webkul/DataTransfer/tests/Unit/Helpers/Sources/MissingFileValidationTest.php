<?php

/**
 * A profile keeps a relative path, so the file it points at can disappear
 * between runs — a cleaned disk, a restored database, a per-tenant storage
 * root. The source opened it without checking, so PHP raised a warning
 * carrying the resolved absolute path and the caller then reported the file
 * as empty rather than missing.
 */

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Sources\CSV;
use Webkul\DataTransfer\Helpers\Sources\Excel;

describe('Missing source file validation', function () {

    it('reports a CSV source file as missing rather than empty', function () {
        Storage::disk('private')->delete('test-absent.csv');

        expect(fn () => new CSV('test-absent.csv', ','))
            ->toThrow(LogicException::class, trans('data_transfer::app.validation.errors.file-missing'));
    });

    it('reports an Excel source file as missing rather than empty', function () {
        Storage::disk('private')->delete('test-absent.xlsx');

        expect(fn () => new Excel('test-absent.xlsx'))
            ->toThrow(LogicException::class, trans('data_transfer::app.validation.errors.file-missing'));
    });

    it('keeps the storage path out of the message a user is shown', function () {
        Storage::disk('private')->delete('test-absent.csv');

        try {
            new CSV('test-absent.csv', ',');

            $this->fail('Expected LogicException was not thrown');
        } catch (LogicException $e) {
            expect($e->getMessage())
                ->not->toContain(Storage::disk('private')->path(''))
                ->not->toContain('test-absent.csv');
        }
    });

    it('raises no PHP warning when the source file is missing', function () {
        Storage::disk('private')->delete('test-absent.csv');

        $warnings = [];

        set_error_handler(function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;

            return true;
        }, E_WARNING);

        try {
            new CSV('test-absent.csv', ',');
        } catch (LogicException) {
            // The throw is the contract; this test only asserts on warnings.
        } finally {
            restore_error_handler();
        }

        expect($warnings)->toBeEmpty();
    });
});
