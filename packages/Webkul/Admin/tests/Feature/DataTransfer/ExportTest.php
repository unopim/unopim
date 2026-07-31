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

it('stores attribute condition operators on the export job', function () {
    $this->loginAsAdmin();

    $code = fake()->unique()->word;

    $exportJob = [
        'code'            => $code,
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => [
            'file_format'       => 'Csv',
            'custom_attributes' => json_encode([
                ['attribute' => 'price', 'operator' => 'less_than', 'value' => '20'],
                ['attribute' => 'release_on', 'operator' => 'between', 'value' => '2024-01-01', 'value2' => '2024-12-31'],
                ['attribute' => 'color', 'operator' => 'in', 'value' => ['red', 'blue']],
                ['attribute' => 'brand', 'operator' => 'empty'],
            ]),
        ],
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $exportJob)
        ->assertStatus(302)
        ->assertSessionHas('success');

    $job = JobInstances::where('code', $code)->first();

    expect($job)->not->toBeNull();

    $conditions = json_decode($job->filters['custom_attributes'], true);

    expect($conditions)->toHaveCount(4)
        ->and($conditions[0])->toMatchArray(['attribute' => 'price', 'operator' => 'less_than', 'value' => '20'])
        ->and($conditions[1])->toMatchArray(['attribute' => 'release_on', 'operator' => 'between', 'value' => '2024-01-01', 'value2' => '2024-12-31'])
        ->and($conditions[3])->toMatchArray(['attribute' => 'brand', 'operator' => 'empty']);
});

it('seeds saved attribute conditions into the edit page field set', function () {
    $this->loginAsAdmin();

    $conditions = [
        ['attribute' => 'color', 'operator' => 'in', 'value' => ['red']],
        ['attribute' => 'brand', 'operator' => 'empty'],
    ];

    $exportJob = JobInstances::factory()->exportJob()->entityProduct()->create([
        'filters' => [
            'file_format'       => 'Csv',
            'custom_attributes' => json_encode($conditions),
        ],
    ]);

    $html = get(route('admin.settings.data_transfer.exports.edit', ['id' => $exportJob->id]))
        ->assertOk()
        ->assertSee("app.component('v-field-attribute-conditions'", false)
        ->getContent();

    preg_match_all("/:saved-values='(\{.*?\})'/s", $html, $matches);

    $seeded = collect($matches[1])
        ->map(fn ($json) => json_decode($json, true))
        ->first(fn ($values) => isset($values['custom_attributes']));

    expect($seeded)->not->toBeNull()
        ->and(json_decode($seeded['custom_attributes'], true))->toBe($conditions);
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

it('keeps the selected entity type filter fields after a validation error reloads the create form', function () {
    $this->loginAsAdmin();

    /**
     * An existing job whose code we duplicate to force a server-side validation failure,
     * which redirects back to the create form with the submitted input flashed as old().
     */
    $existing = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->from(route('admin.settings.data_transfer.exports.create'))
        ->post(route('admin.settings.data_transfer.exports.store'), [
            'code'        => $existing->code,
            'entity_type' => 'products',
            'filters'     => [
                'sku' => 'TEST-SKU-123',
            ],
        ])
        ->assertRedirect(route('admin.settings.data_transfer.exports.create'))
        ->assertSessionHasErrors('code');

    $content = get(route('admin.settings.data_transfer.exports.create'))
        ->assertStatus(200)
        ->getContent();

    preg_match('/filterFields: \(window\.unopim\?\.fieldSets\?\.\[setsKey\] \?\? \{\}\)\[("[a-z-]+")\]/', $content, $seed);

    expect($seed[1] ?? null)->toBe('"products"');

    preg_match("/window\.unopim\.fieldSets\['([a-f0-9]+)'\] = (\{.*?\});/s", $content, $registry);
    preg_match('/const setsKey = "([a-f0-9]+)"/', $content, $key);

    expect($key[1] ?? null)->toBe($registry[1] ?? null);

    $sets = json_decode($registry[2], true);

    expect(array_column($sets['products'], 'name'))->toContain('sku');
});

it('stores the new output options on the export job', function () {
    $this->loginAsAdmin();

    $code = fake()->unique()->word;

    $exportJob = [
        'code'            => $code,
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Csv',
            'header_row'  => '0',
            'use_labels'  => '1',
            'date_format' => 'd/m/Y',
            'file_path'   => '[code]_[date]',
        ],
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $exportJob)
        ->assertStatus(302)
        ->assertSessionHas('success');

    $job = JobInstances::where('code', $code)->first();

    expect($job)->not->toBeNull()
        ->and($job->filters['header_row'])->toBe('0')
        ->and($job->filters['use_labels'])->toBe('1')
        ->and($job->filters['date_format'])->toBe('d/m/Y')
        ->and($job->filters['file_path'])->toBe('[code]_[date]');
});

it('rejects an invalid date_format on the export job', function () {
    $this->loginAsAdmin();

    $exportJob = [
        'code'            => fake()->unique()->word,
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Csv',
            'date_format' => 'not-a-format',
        ],
    ];

    postJson(route('admin.settings.data_transfer.exports.store'), $exportJob)
        ->assertJsonValidationErrors(['filters[date_format]']);
});
