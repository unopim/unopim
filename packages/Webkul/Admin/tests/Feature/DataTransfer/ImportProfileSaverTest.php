<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\User\Models\Admin;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Storage::fake();
    Storage::fake('private');

    $this->loginAsAdmin();

    $this->otherAdmin = Admin::factory()->create();
});

function importProfilePayload(array $overrides = []): array
{
    return [
        'code'                => fake()->unique()->word,
        'entity_type'         => 'users',
        'field_separator'     => ',',
        'allowed_errors'      => 0,
        'file'                => UploadedFile::fake()->create('users.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
        ...$overrides,
    ];
}

it('records the admin who creates an import profile as its editor and file uploader and ignores posted ones', function () {
    $payload = importProfilePayload(['updated_by' => $this->otherAdmin->id, 'file_uploaded_by' => $this->otherAdmin->id]);

    postJson(route('admin.settings.data_transfer.imports.store'), $payload)->assertStatus(302);

    $profile = JobInstances::query()->where('code', $payload['code'])->firstOrFail();

    expect($profile->updated_by)->toBe(auth()->guard('admin')->id())
        ->and($profile->file_uploaded_by)->toBe(auth()->guard('admin')->id());
});

it('records the editor but keeps the file uploader when a profile is saved again without a new file', function () {
    $importJob = JobInstances::factory()->importJob()->create(['entity_type' => 'users']);
    $importJob->forceFill(['updated_by' => $this->otherAdmin->id, 'file_uploaded_by' => $this->otherAdmin->id])->saveQuietly();

    $payload = importProfilePayload(['code' => $importJob->code]);
    unset($payload['file']);

    putJson(route('admin.settings.data_transfer.imports.update', ['id' => $importJob->id]), $payload)->assertStatus(302);

    $importJob->refresh();

    expect($importJob->updated_by)->toBe(auth()->guard('admin')->id())
        ->and($importJob->file_uploaded_by)->toBe($this->otherAdmin->id);
});

it('records the admin who uploads a new file to an import profile as editor and uploader and ignores posted ones', function () {
    $importJob = JobInstances::factory()->importJob()->create(['entity_type' => 'users']);
    $importJob->forceFill(['updated_by' => $this->otherAdmin->id, 'file_uploaded_by' => $this->otherAdmin->id])->saveQuietly();

    putJson(
        route('admin.settings.data_transfer.imports.update', ['id' => $importJob->id]),
        importProfilePayload(['code' => $importJob->code, 'updated_by' => $this->otherAdmin->id, 'file_uploaded_by' => $this->otherAdmin->id])
    )->assertStatus(302);

    $importJob->refresh();

    expect($importJob->updated_by)->toBe(auth()->guard('admin')->id())
        ->and($importJob->file_uploaded_by)->toBe(auth()->guard('admin')->id());
});

it('stores each uploaded import file under its own key even for the same name in the same second', function () {
    $first = importProfilePayload(['file' => UploadedFile::fake()->createWithContent('users.csv', 'first')]);
    $second = importProfilePayload(['file' => UploadedFile::fake()->createWithContent('users.csv', 'second')]);

    postJson(route('admin.settings.data_transfer.imports.store'), $first)->assertStatus(302);
    postJson(route('admin.settings.data_transfer.imports.store'), $second)->assertStatus(302);

    $firstPath = JobInstances::query()->where('code', $first['code'])->value('file_path');
    $secondPath = JobInstances::query()->where('code', $second['code'])->value('file_path');

    expect($firstPath)->not->toBe($secondPath)
        ->and(Storage::disk('private')->get($firstPath))->toBe('first');
});
