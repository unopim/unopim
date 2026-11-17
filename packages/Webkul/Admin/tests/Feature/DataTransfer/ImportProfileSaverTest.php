<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\User\Models\Admin;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Storage::fake();

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

it('records the admin who creates an import profile and ignores a posted saver', function () {
    $payload = importProfilePayload(['updated_by' => $this->otherAdmin->id]);

    postJson(route('admin.settings.data_transfer.imports.store'), $payload)->assertStatus(302);

    expect(JobInstances::query()->where('code', $payload['code'])->value('updated_by'))
        ->toBe(auth()->guard('admin')->id());
});

it('records the admin who last saves an import profile and ignores a posted saver', function () {
    $importJob = JobInstances::factory()->importJob()->create(['entity_type' => 'users']);
    $importJob->forceFill(['updated_by' => $this->otherAdmin->id])->saveQuietly();

    putJson(
        route('admin.settings.data_transfer.imports.update', ['id' => $importJob->id]),
        importProfilePayload(['code' => $importJob->code, 'updated_by' => $this->otherAdmin->id])
    )->assertStatus(302);

    expect($importJob->fresh()->updated_by)->toBe(auth()->guard('admin')->id());
});
