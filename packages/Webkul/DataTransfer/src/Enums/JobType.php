<?php

namespace Webkul\DataTransfer\Enums;

use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;

enum JobType: string
{
    case IMPORT = 'import';

    case EXPORT = 'export';

    case SYSTEM = 'system';

    /**
     * `job_track.type` is an unconstrained string column, so an unknown value keeps the import
     * behaviour callers relied on before this enum existed.
     */
    public static function fromTrack(JobTrackContract $jobTrack): self
    {
        return self::tryFrom((string) $jobTrack->type) ?? self::IMPORT;
    }

    public function isExport(): bool
    {
        return $this === self::EXPORT;
    }

    /**
     * Export copy lives under `-export` suffixed keys; import and system jobs share the base key.
     */
    public function trackerMessage(string $key): string
    {
        return trans('admin::app.settings.data-transfer.tracker.'.$key.($this->isExport() ? '-export' : ''));
    }

    /**
     * Export permissions are grouped under the singular `data_transfer.export` key in acl.php.
     */
    public function executePermission(): string
    {
        return $this->isExport()
            ? 'data_transfer.export.execute'
            : 'data_transfer.imports.execute';
    }
}
