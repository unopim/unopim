<?php

namespace Webkul\DataTransfer\Tests\Unit\Helpers\Importers\User;

use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\User\Importer;
use Webkul\DataTransfer\Helpers\Source;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->jobTrack = JobTrack::factory()->create([
        'user_id' => auth()->guard('admin')->id(),
        'meta'    => ['batch_count' => 0, 'updated_by' => auth()->guard('admin')->id()],
        'action'  => Import::ACTION_APPEND,
    ]);

    $this->importer = app(Importer::class);
    $this->importer->setImport($this->jobTrack);
    $this->importer->setErrorHelper(app(Error::class));

    $source = mock(Source::class);
    $source->shouldReceive('getColumnNames')->andReturn($this->importer->getValidColumnNames());
    $source->shouldReceive('rewind');
    $source->shouldReceive('valid')->andReturn(false);
    $this->importer->setSource($source);

    $this->importer->validateData();

    $this->userRow = fn (string $email, string $timezone): array => [
        'name'           => 'Timezone User',
        'email'          => $email,
        'role_name'      => Role::firstOrFail()->name,
        'status'         => 'active',
        'timezone'       => $timezone,
        'ui_locale_code' => Locale::where('code', 'en_US')->value('code'),
    ];
});

it('rejects a row with an invalid timezone and accepts blank, valid or legacy ones', function () {
    expect($this->importer->validateRow(($this->userRow)('invalid.tz@example.com', 'Mars/Phobos'), 1))->toBeFalse()
        ->and($this->importer->validateRow(($this->userRow)('blank.tz@example.com', ''), 2))->toBeTrue()
        ->and($this->importer->validateRow(($this->userRow)('valid.tz@example.com', 'Asia/Kolkata'), 3))->toBeTrue()
        ->and($this->importer->validateRow(($this->userRow)('legacy.tz@example.com', 'Asia/Calcutta'), 4))->toBeTrue();
});

it('stores the app timezone for a blank imported timezone and keeps a valid one', function () {
    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $this->jobTrack->id,
        'data'         => [
            ($this->userRow)('blank.tz@example.com', ''),
            ($this->userRow)('valid.tz@example.com', 'Asia/Kolkata'),
        ],
    ]);

    $this->importer->importBatch($batch);

    expect(Admin::where('email', 'blank.tz@example.com')->value('timezone'))->toBe(config('app.timezone'))
        ->and(Admin::where('email', 'valid.tz@example.com')->value('timezone'))->toBe('Asia/Kolkata');
});
