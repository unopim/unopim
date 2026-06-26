<?php

use Illuminate\Support\Carbon;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function makeProductExporter(array $filters): Exporter
{
    $jobInstance = JobInstances::create([
        'code'                => 'demo_export',
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => $filters,
    ]);

    $jobTrack = JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->getFilters();

    return $exporter;
}

function exporterFileName(Exporter $exporter): string
{
    $method = new ReflectionMethod($exporter, 'getFileName');
    $method->setAccessible(true);

    return $method->invoke($exporter);
}

describe('file_path token expansion', function () {
    it('falls back to the default {code}-{entity_type}.{ext} name when file_path is empty', function () {
        $exporter = makeProductExporter(['file_format' => 'Csv']);

        expect(exporterFileName($exporter))->toBe('demo_export-products.csv');
    });

    it('expands [code] and [entity_type] tokens', function () {
        $exporter = makeProductExporter(['file_format' => 'Csv', 'file_path' => '[code]_[entity_type]']);

        expect(exporterFileName($exporter))->toBe('demo_export_products.csv');
    });

    it('also accepts curly-brace tokens', function () {
        Carbon::setTestNow(Carbon::parse('2026-06-16 10:30:00'));

        $exporter = makeProductExporter(['file_format' => 'Csv', 'file_path' => '{code}_{date}']);

        expect(exporterFileName($exporter))->toBe('demo_export_2026-06-16.csv');

        Carbon::setTestNow();
    });

    it('ignores a trailing export extension typed in the pattern instead of mangling it', function () {
        $exporter = makeProductExporter(['file_format' => 'Csv', 'file_path' => '{code}.csv']);

        expect(exporterFileName($exporter))->toBe('demo_export.csv');
    });

    it('expands the [date] token using the current date', function () {
        Carbon::setTestNow(Carbon::parse('2026-06-16 10:30:00'));

        $exporter = makeProductExporter(['file_format' => 'Xlsx', 'file_path' => 'export-[date]']);

        expect(exporterFileName($exporter))->toBe('export-2026-06-16.xlsx');

        Carbon::setTestNow();
    });

    it('sanitizes path separators and traversal sequences in file_path', function () {
        $exporter = makeProductExporter(['file_format' => 'Csv', 'file_path' => '../../etc/passwd']);

        // Path separators and dots are stripped so the name can never escape the export directory.
        expect(exporterFileName($exporter))->toBe('etcpasswd.csv');
    });

    it('falls back to the default name when file_path has no usable characters', function () {
        $exporter = makeProductExporter(['file_format' => 'Csv', 'file_path' => '/////']);

        expect(exporterFileName($exporter))->toBe('demo_export-products.csv');
    });
});

describe('header row export parameter', function () {
    it('writes the header row by default when header_row is not set', function () {
        expect(makeProductExporter(['file_format' => 'Csv'])->getExportParameter()['writeHeaders'])->toBeTrue();
    });

    it('disables the header row when the header_row filter is 0', function () {
        expect(makeProductExporter(['file_format' => 'Csv', 'header_row' => '0'])->getExportParameter()['writeHeaders'])->toBeFalse();
    });

    it('produces no header labels when use_labels is off', function () {
        expect(makeProductExporter(['file_format' => 'Csv'])->getExportParameter()['headerLabels'])->toBe([]);
    });
});

describe('date value formatting', function () {
    function formatDate(mixed $value, string $format): mixed
    {
        $exporter = app(Exporter::class);
        $method = new ReflectionMethod($exporter, 'formatDateValue');
        $method->setAccessible(true);

        return $method->invoke($exporter, $value, $format);
    }

    it('formats a parseable date value using the configured format', function () {
        expect(formatDate('2026-06-16', 'd/m/Y'))->toBe('16/06/2026');
    });

    it('formats a datetime value preserving the time portion', function () {
        expect(formatDate('2026-06-16 14:05:09', 'Y-m-d H:i:s'))->toBe('2026-06-16 14:05:09');
    });

    it('returns the original value unchanged when it cannot be parsed as a date', function () {
        expect(formatDate('not-a-date', 'Y-m-d'))->toBe('not-a-date');
    });

    it('returns empty and null values unchanged', function () {
        expect(formatDate('', 'Y-m-d'))->toBe('')
            ->and(formatDate(null, 'Y-m-d'))->toBeNull();
    });
});

describe('use labels value resolution', function () {
    function optionAttribute(string $type = 'select'): object
    {
        $option = fn (string $code, array $labels) => (object) [
            'code'         => $code,
            'translations' => collect($labels)->map(fn ($label, $locale) => (object) ['locale' => $locale, 'label' => $label])->values(),
        ];

        return (object) [
            'type'    => $type,
            'code'    => 'color',
            'options' => collect([
                $option('red', ['en_US' => 'Red', 'fr_FR' => 'Rouge']),
                $option('blue', ['en_US' => 'Blue']),
            ]),
        ];
    }

    function resolveLabels(object $attribute, mixed $value, ?string $locale): mixed
    {
        $exporter = app(Exporter::class);
        $method = new ReflectionMethod($exporter, 'resolveValueLabels');
        $method->setAccessible(true);

        return $method->invoke($exporter, $attribute, $value, $locale);
    }

    it('maps a select option code to its label in the row locale', function () {
        expect(resolveLabels(optionAttribute('select'), 'red', 'fr_FR'))->toBe('Rouge');
    });

    it('maps each code of a multiselect value to its label', function () {
        expect(resolveLabels(optionAttribute('multiselect'), ['red', 'blue'], 'en_US'))
            ->toBe(['Red', 'Blue']);
    });

    it('falls back to the code when no label exists for that locale', function () {
        expect(resolveLabels(optionAttribute('select'), 'blue', 'fr_FR'))->toBe('blue');
    });

    it('falls back to the code when the option code is unknown', function () {
        expect(resolveLabels(optionAttribute('select'), 'green', 'en_US'))->toBe('green');
    });

    it('leaves non-option attribute types unchanged', function () {
        $text = (object) ['type' => 'text', 'code' => 'name', 'options' => collect()];

        expect(resolveLabels($text, 'some text', 'en_US'))->toBe('some text');
    });

    it('leaves empty values unchanged', function () {
        expect(resolveLabels(optionAttribute('select'), '', 'en_US'))->toBe('')
            ->and(resolveLabels(optionAttribute('select'), null, 'en_US'))->toBeNull();
    });
});
