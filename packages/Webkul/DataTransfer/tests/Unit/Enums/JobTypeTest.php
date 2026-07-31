<?php

use Webkul\DataTransfer\Enums\JobType;
use Webkul\DataTransfer\Models\JobTrack;

function jobTrackOfType(string $type): JobTrack
{
    return new JobTrack(['type' => $type]);
}

describe('JobType', function () {
    it('resolves the type of a job track', function (string $type, JobType $expected) {
        expect(JobType::fromTrack(jobTrackOfType($type)))->toBe($expected);
    })->with([
        ['import', JobType::IMPORT],
        ['export', JobType::EXPORT],
        ['system', JobType::SYSTEM],
    ]);

    it('falls back to import for an unknown type', function () {
        expect(JobType::fromTrack(jobTrackOfType('something-else')))->toBe(JobType::IMPORT);
    });

    it('reads export copy for exports and base copy for everything else', function () {
        expect(JobType::EXPORT->trackerMessage('paused'))
            ->toBe(trans('admin::app.settings.data-transfer.tracker.paused-export'));

        expect(JobType::IMPORT->trackerMessage('paused'))
            ->toBe(trans('admin::app.settings.data-transfer.tracker.paused'));

        expect(JobType::SYSTEM->trackerMessage('paused'))
            ->toBe(trans('admin::app.settings.data-transfer.tracker.paused'));
    });

    it('authorizes each type against its own execute permission', function () {
        expect(JobType::EXPORT->executePermission())->toBe('data_transfer.export.execute');
        expect(JobType::IMPORT->executePermission())->toBe('data_transfer.imports.execute');
        expect(JobType::SYSTEM->executePermission())->toBe('data_transfer.imports.execute');
    });
});
