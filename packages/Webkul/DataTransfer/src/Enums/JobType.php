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
    /**
     * @return array<string, string>
     */
    public function trackerMessages(): array
    {
        return [
            'pauseFailed'  => $this->trackerMessage('pause-failed'),
            'resumeFailed' => $this->trackerMessage('resume-failed'),
            'cancelFailed' => $this->trackerMessage('cancel-failed'),
        ];
    }

    /**
     * System jobs are created by the application itself, so they have no editable job instance.
     */
    public function editRouteName(): ?string
    {
        return match ($this) {
            self::IMPORT => 'admin.settings.data_transfer.imports.edit',
            self::EXPORT => 'admin.settings.data_transfer.exports.edit',
            self::SYSTEM => null,
        };
    }

    public function editPermission(): ?string
    {
        return match ($this) {
            self::IMPORT => 'data_transfer.imports.edit',
            self::EXPORT => 'data_transfer.export.edit',
            self::SYSTEM => null,
        };
    }

    public function executePermission(): string
    {
        return $this->isExport()
            ? 'data_transfer.export.execute'
            : 'data_transfer.imports.execute';
    }
}
