<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should return the Import index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.data_transfer.imports.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.index.title'));
});

it('should show the create import job form', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.data_transfer.imports.create'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.create.title'));
});

it('should return the import job datagrid', function () {
    $this->loginAsAdmin();

    $importJob = JobInstances::factory()->importJob()->entityProduct()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.settings.data_transfer.imports.index'));

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

it('should show the edit form for import job', function () {
    $this->loginAsAdmin();

    $importJob = JobInstances::factory()->importJob()->entityProduct()->create();
    $response = get(route('admin.settings.data_transfer.imports.edit', ['id' => $importJob->id]));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.edit.title'));
});

it('should create the import job', function () {
    $this->loginAsAdmin();
    Storage::fake();

    $importJob = [
        'code'                => fake()->unique()->word,
        'entity_type'         => 'products',
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];

    $response = postJson(route('admin.settings.data_transfer.imports.store'), $importJob);
    $response->assertStatus(302)
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $importJob['code']]);
});

it('should create the category import job', function () {
    $this->loginAsAdmin();
    Storage::fake();

    $importJob = [
        'code'                => fake()->unique()->word,
        'entity_type'         => 'categories',
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];

    $response = postJson(route('admin.settings.data_transfer.imports.store'), $importJob);

    $response->assertStatus(302)
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $importJob['code']]);
});

it('should create the import job only with unique code', function () {
    $this->loginAsAdmin();
    Storage::fake();

    $importJob = JobInstances::factory()->entityProduct()->importJob()->create();

    $updatedJob = [
        'code'                => $importJob->code,
        'entity_type'         => 'categories',
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];

    $response = postJson(route('admin.settings.data_transfer.imports.store'), $updatedJob);

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

it('should give validation messages during create of import job', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.data_transfer.imports.store'), [])
        ->assertJsonValidationErrors([
            'code',
            'entity_type',
            'file',
            'field_separator',
            'allowed_errors',
            'validation_strategy',
            'action',
        ]);
});

it('should give validation messages for field_separator during create of import job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'        => fake()->unique()->word,
        'entity_type' => 'products',
    ];

    postJson(route('admin.settings.data_transfer.imports.store'), $job)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'field_separator',
        ]);
});

it('should give validation messages for file extension during create of import job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'                => fake()->unique()->word,
        'entity_type'         => 'categories',
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.doc'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];

    postJson(route('admin.settings.data_transfer.imports.store'), $job)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'file',
        ]);
});

it('should give validation messages for correct field_separator during create of import job', function () {
    $this->loginAsAdmin();

    $job = [
        'code'                => fake()->unique()->word,
        'entity_type'         => 'categories',
        'field_separator'     => ':',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];

    postJson(route('admin.settings.data_transfer.imports.store'), $job)
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'field_separator',
        ]);
});

it('should edit the import job', function () {
    $this->loginAsAdmin();

    $importJob = JobInstances::factory()->importJob()->entityProduct()->create();

    $updatedJob = [
        'code'                => $importJob->code,
        'entity_type'         => $importJob->entity_type,
        'field_separator'     => ',',
        'type'                => 'import',
        'allowed_errors'      => fake()->numberBetween(0, 20),
        'file'                => UploadedFile::fake()->create('product.xlsx'),
        'action'              => 'append',
        'validation_strategy' => 'stop-on-errors',
    ];

    putJson(route('admin.settings.data_transfer.imports.update', ['id' => $importJob->id]), $updatedJob)
        ->assertStatus(302)
        ->assertSessionHas('success')
        ->assertRedirect(route('admin.settings.data_transfer.imports.import-view', $importJob->id));
    unset($updatedJob['file']);

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), $updatedJob);
});

it('should delete the import job', function () {
    $this->loginAsAdmin();

    $importJob = JobInstances::factory()->importJob()->entityCategory()->create();

    delete(route('admin.settings.data_transfer.imports.delete', ['id' => $importJob->id]))
        ->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.settings.data-transfer.imports.delete-success'),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(JobInstances::class), ['code' => $importJob->code]);
});
