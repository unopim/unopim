<?php

use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should return the Export index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.data_transfer.exports.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.index.title'));
});

it('should show the create export job form', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.data_transfer.exports.create'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.create.title'));
});

it('should return the Export job datagrid', function () {
    $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.settings.data_transfer.exports.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should show the edit form for export job', function () {
    $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();
    $response = get(route('admin.settings.data_transfer.exports.edit', ['id' => $exportJob->id]));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.edit.title'));
});

it('should create the export job', function () {
    $this->loginAsAdmin();

    $exportJob = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Csv',
            'with_media'  => 1,
        ],
    ];

    $response = postJson(route('admin.settings.data_transfer.exports.store'), $exportJob);
    $response->assertStatus(302)
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $exportJob['code']]);
});

it('should create the category export job', function () {
    $this->loginAsAdmin();

    $exportJob = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'categories',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Csv',
            'with_media'  => 1,
        ],
    ];

    $response = postJson(route('admin.settings.data_transfer.exports.store'), $exportJob);
    $response->assertStatus(302)
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $exportJob['code']]);
});

it('should create the export job only with unique code', function () {
    $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $updatedJob = [
        'code'            => $exportJob->code,
        'entity_type'     => 'categories',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Csv',
            'with_media'  => 1,
        ],
    ];

    $response = postJson(route('admin.settings.data_transfer.exports.store'), $updatedJob);

    $response->assertJson([
        'message' => trans('validation.unique', ['attribute' => 'code']),
    ]);

    $this->assertDatabaseMissing($this->getFullTableName(JobInstances::class),
        [
            'code'        => $updatedJob['code'],
            'entity_type' => $updatedJob['entity_type'],
        ]
    );
});

it('should give validation messages during create of export job', function () {
    $this->loginAsAdmin();

    $updatedJob = [
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $updatedJob)
        ->assertJsonValidationErrors([
            'code',
            'entity_type',
        ])->assertJsonFragment([
            'code'                => [trans('validation.required', ['attribute' => 'code'])],
            'entity_type'         => [trans('validation.required', ['attribute' => 'entity type'])],
        ]);
});

it('should give validation messages for field_separator during create of export job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'products',
        'filters'         => [
            'file_format' => 'Csv',
            'with_media'  => 1,
        ],
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $job)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'field_separator',
        ]);
});

it('should give validation messages for correct field_separator during create of export job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'products',
        'field_separator' => ':',
        'filters'         => [
            'file_format' => 'Csv',
            'with_media'  => 1,
        ],
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $job)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'field_separator',
        ]);
});

it('should create xlsx fromat job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'products',
        'filters'         => [
            'file_format' => 'Xlsx',
            'with_media'  => 1,
        ],
    ];

    $response = postJson(route('admin.settings.data_transfer.exports.store'), $job);
    $response->assertStatus(302)
        ->assertSessionHas('success');

    $exportjob = JobInstances::where('code', $job['code']);
    expect($exportjob->first()->filters)->toEqual($job['filters']);

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $job['code']]);
});

it('should edit the export job', function () {
    $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create();

    $updatedJob = [
        'code'            => $exportJob->code,
        'entity_type'     => $exportJob->entity_type,
        'field_separator' => ',',
        'filters'         => [
            'with_media'  => 0,
            'file_format' => 'Csv',
        ],
    ];

    putJson(route('admin.settings.data_transfer.exports.update', ['id' => $exportJob->id]), $updatedJob)
        ->assertStatus(302)
        ->assertSessionHas('success')
        ->assertRedirect(route('admin.settings.data_transfer.exports.export-view', $exportJob->id));

    $exportjob = JobInstances::where('code', $exportJob->code);
    expect($exportjob->first()->filters)->toEqual($updatedJob['filters']);
    unset($updatedJob['filters']);

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), $updatedJob);
});

it('should delete the export job', function () {
    $this->loginAsAdmin();

    $exportJob = JobInstances::factory()->exportJob()->entityCategory()->create();

    delete(route('admin.settings.data_transfer.exports.delete', ['id' => $exportJob->id]))
        ->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.settings.data-transfer.exports.delete-success'),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(JobInstances::class), ['code' => $exportJob->code]);
});
