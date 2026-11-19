<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->loginAsAdmin();

    Storage::fake();

    config(['core' => [
        [
            'key'    => 'general.testgroup',
            'name'   => 'Test Group',
            'fields' => [
                ['name' => 'text_field', 'title' => 'Text', 'type' => 'text'],
                ['name' => 'logo', 'title' => 'Logo', 'type' => 'image'],
            ],
        ],
    ]]);
});

/**
 * Post the test configuration group with real uploaded files attached.
 */
function postConfigurationWithFiles(array $input, array $files): TestResponse
{
    $request = Request::create(
        route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'testgroup']),
        'POST',
        $input,
        [],
        $files,
        ['HTTP_ACCEPT' => 'application/json'],
    );

    return TestResponse::fromBaseResponse(app(Kernel::class)->handle($request));
}

it('rejects a file posted at a text field alongside its text value and stores nothing', function () {
    postConfigurationWithFiles(
        ['general' => ['testgroup' => ['text_field' => 'ok']]],
        ['general' => ['testgroup' => ['text_field' => UploadedFile::fake()->create('payload.svg', 1)]]],
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('general.testgroup.text_field');

    $this->assertDatabaseMissing('core_config', ['code' => 'general.testgroup.text_field']);
    expect(Storage::allFiles('configuration'))->toBeEmpty();
});

it('stores a file posted at a declared image field', function () {
    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'testgroup']), [
        'general' => ['testgroup' => [
            'text_field' => 'ok',
            'logo'       => UploadedFile::fake()->image('logo.png'),
        ]],
    ])->assertRedirect();

    $stored = Storage::allFiles('configuration');

    expect($stored)->toHaveCount(1);
    $this->assertDatabaseHas('core_config', ['code' => 'general.testgroup.logo', 'value' => $stored[0]]);
    $this->assertDatabaseHas('core_config', ['code' => 'general.testgroup.text_field', 'value' => 'ok']);
});
