<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Sources\CSV;

describe('CSV Source Optimizations', function () {
    it('reads CSV files with unlimited line length', function () {
        /** Create a test CSV with a row longer than 4096 bytes */
        $longValue = str_repeat('abcdefghij', 500); // 5000 chars

        $csvContent = "sku,name,description\n";
        $csvContent .= "test-sku,\"Test Product\",\"{$longValue}\"\n";

        Storage::disk('private')->put('test-csv-long-rows.csv', $csvContent);

        $source = new CSV('test-csv-long-rows.csv', ',');

        $source->rewind();

        expect($source->valid())->toBeTrue();

        $row = $source->current();

        expect($row['sku'])->toBe('test-sku');
        expect($row['name'])->toBe('Test Product');
        expect(strlen($row['description']))->toBe(5000);

        /** Cleanup */
        Storage::disk('private')->delete('test-csv-long-rows.csv');
    });

    it('reads standard CSV files correctly', function () {
        $csvContent = "sku,name,status\n";
        $csvContent .= "prod-1,\"Product One\",1\n";
        $csvContent .= "prod-2,\"Product Two\",0\n";
        $csvContent .= "prod-3,\"Product Three\",1\n";

        Storage::disk('private')->put('test-csv-standard.csv', $csvContent);

        $source = new CSV('test-csv-standard.csv', ',');

        $source->rewind();

        $rows = [];

        while ($source->valid()) {
            $rows[] = $source->current();
            $source->next();
        }

        expect($rows)->toHaveCount(3);
        expect($rows[0]['sku'])->toBe('prod-1');
        expect($rows[1]['sku'])->toBe('prod-2');
        expect($rows[2]['sku'])->toBe('prod-3');

        /** Cleanup */
        Storage::disk('private')->delete('test-csv-standard.csv');
    });
});

describe('Date Formatting Optimization', function () {
    it('formats standard MySQL datetime without Carbon', function () {
        $date = '2025-01-15 10:30:00';

        /** Simulate the fast string-based date conversion */
        if (strlen($date) === 19 && $date[4] === '-' && $date[10] === ' ') {
            $result = substr($date, 0, 10).'T'.substr($date, 11).'.000000Z';
        }

        expect($result)->toBe('2025-01-15T10:30:00.000000Z');
    });

    it('produces same output as Carbon for standard dates', function () {
        $date = '2025-06-20 14:45:30';

        /** Fast string method */
        $fastResult = substr($date, 0, 10).'T'.substr($date, 11).'.000000Z';

        /** Carbon method */
        $carbonResult = \Illuminate\Support\Carbon::parse($date)->toJson();

        expect($fastResult)->toBe($carbonResult);
    });
});
