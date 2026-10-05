<?php

namespace Webkul\Admin\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Webkul\Admin\Exports\DataGridExport;
use Webkul\Admin\Jobs\Concerns\ResolvesDataGridSelection;
use Webkul\DataGrid\DataGrid;
use Webkul\Notification\Events\NotificationEvent;

/**
 * Exports every record matching a grid's filters to a file on the private disk, then notifies the admin with a download link.
 *
 * Rows are spooled to a temporary file slice by slice, so memory stays flat whatever the size of the selection;
 * the header is only known once every row is built because each record may add columns.
 */
class ExportDataGridSelection implements ShouldQueue
{
    use Queueable, ResolvesDataGridSelection;

    /**
     * Records built per slice.
     */
    public const CHUNK_SIZE = 500;

    public const DISK = 'private';

    /**
     * Directory holding each admin's finished exports, one sub-directory per admin.
     */
    public const DIRECTORY = 'exports/datagrid';

    /**
     * Finished exports older than this many hours are removed when the admin starts a new one.
     */
    public const RETENTION_HOURS = 24;

    public const MAX_TIMEOUT = 86400;

    public $tries = 1;

    public $timeout = self::MAX_TIMEOUT;

    /**
     * @param  class-string<DataGrid>  $dataGrid
     * @param  array<string, mixed>  $gridParams
     * @param  string  $translationKey  Prefix holding the `title`, `completed`, `failed` and `too-many-rows` notification strings.
     */
    public function __construct(
        protected string $dataGrid,
        protected array $gridParams,
        protected string $format,
        protected int $adminId,
        protected string $translationKey,
        protected string $downloadRoute,
    ) {}

    /**
     * Build the export file and notify the admin once it is ready.
     */
    public function handle(): void
    {
        $this->pruneExpiredExports();

        $spool = tmpfile();

        $columns = [];

        $rows = 0;

        $records = 0;

        $limit = $this->rowLimit();

        try {
            $this->withGridRequest(function (DataGrid $dataGrid) use ($spool, &$columns, &$rows, &$records, $limit): void {
                $dataGrid->getMatchingIds()
                    ->chunk(static::CHUNK_SIZE)
                    ->each(function (LazyCollection $ids) use ($spool, &$columns, &$rows, &$records, $limit): bool {
                        $slice = app($this->dataGrid)->getExportableRows($ids->values()->all());

                        $columns = array_values(array_unique([...$columns, ...$slice['columns']]));

                        foreach ($slice['records'] as $record) {
                            fwrite($spool, json_encode($record, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
                        }

                        $rows += count($slice['records']);

                        $records += $ids->count();

                        return $limit === null || $rows <= $limit;
                    });
            });

            if ($limit !== null && $rows > $limit) {
                $this->notify(trans($this->translationKey.'.too-many-rows', ['limit' => $limit]));

                return;
            }

            $path = sprintf('%s/%d/%s.%s', static::DIRECTORY, $this->adminId, Str::uuid(), $this->format);

            $this->writeFile($path, $columns, $spool);
        } finally {
            fclose($spool);
        }

        $this->notify(trans($this->translationKey.'.completed', ['count' => $records]), basename($path));
    }

    /**
     * Notify the admin that the export could not be built.
     */
    public function failed(?Throwable $exception): void
    {
        $this->notify(trans($this->translationKey.'.failed'));
    }

    /**
     * Most data rows the requested format can hold, or null when it has no limit.
     */
    protected function rowLimit(): ?int
    {
        return match ($this->format) {
            'xls'   => 65535,
            'xlsx'  => 1048575,
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $columns
     * @param  resource  $spool
     */
    protected function writeFile(string $path, array $columns, $spool): void
    {
        if ($this->format !== 'csv') {
            Excel::store(
                new DataGridExport(['columns' => $columns, 'records' => $this->spooledRecords($spool)]),
                $path,
                static::DISK,
                $this->format === 'xls' ? ExcelWriter::XLS : ExcelWriter::XLSX,
            );

            return;
        }

        $csv = tmpfile();

        fputcsv($csv, $columns, escape: '');

        foreach ($this->spooledRecords($spool) as $record) {
            fputcsv($csv, array_map(fn (string $column) => $this->cell($record[$column] ?? ''), $columns), escape: '');
        }

        rewind($csv);

        Storage::disk(static::DISK)->writeStream($path, $csv);

        fclose($csv);
    }

    /**
     * @param  resource  $spool
     * @return LazyCollection<int, array<string, mixed>>
     */
    protected function spooledRecords($spool): LazyCollection
    {
        return LazyCollection::make(function () use ($spool) {
            rewind($spool);

            while (($line = fgets($spool)) !== false) {
                yield json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            }
        });
    }

    protected function cell(mixed $value): string
    {
        return is_array($value) ? (string) json_encode($value) : (string) $value;
    }

    protected function notify(string $description, ?string $file = null): void
    {
        event(new NotificationEvent([
            'type'         => 'export',
            'route'        => $file ? $this->downloadRoute : null,
            'route_params' => $file ? ['file' => $file] : null,
            'title'        => trans($this->translationKey.'.title'),
            'description'  => $description,
            'user_ids'     => [$this->adminId],
        ]));
    }

    protected function pruneExpiredExports(): void
    {
        $disk = Storage::disk(static::DISK);

        $expiresBefore = now()->subHours(static::RETENTION_HOURS)->getTimestamp();

        foreach ($disk->files(static::DIRECTORY.'/'.$this->adminId) as $file) {
            if ($disk->lastModified($file) < $expiresBefore) {
                $disk->delete($file);
            }
        }
    }
}
