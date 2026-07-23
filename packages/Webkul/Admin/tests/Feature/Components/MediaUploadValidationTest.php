<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Requests\AppearanceForm;
use Webkul\Admin\Http\Requests\UserForm;
use Webkul\AdminApi\Http\Requests\Catalog\StoreSwatchMediaRequest;
use Webkul\Core\Repositories\LocaleRepository;

function appearanceUploadRequest(UploadedFile $file): AppearanceForm
{
    $request = AppearanceForm::create(
        uri: '/admin/settings/appearance',
        method: 'PUT',
        files: ['logo_image' => $file],
    );

    $request->setContainer(app());
    $request->setRedirector(app('redirect'));

    return $request;
}

it('applies full validation rules to a scalar executable appearance upload', function () {
    $request = appearanceUploadRequest(
        UploadedFile::fake()->create('shell.php', 10, 'application/x-php')
    );

    try {
        $request->validateResolved();
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('logo_image');

        return;
    }

    $this->fail('The executable upload unexpectedly passed validation.');
});

it('rejects image content with an active-content filename', function () {
    $request = appearanceUploadRequest(
        UploadedFile::fake()->image('payload.html')
    );

    expect(fn () => $request->validateResolved())
        ->toThrow(ValidationException::class);
});

it('accepts a valid scalar PNG upload', function () {
    $request = appearanceUploadRequest(
        UploadedFile::fake()->image('logo.png')
    );

    $request->validateResolved();

    expect($request->file('logo_image'))->toBeInstanceOf(UploadedFile::class);
});

it('applies the complete user image rules to scalar uploads', function () {
    $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');
    $request = new UserForm(app(LocaleRepository::class));
    $request->initialize(files: ['image' => $file]);

    $validator = Validator::make(
        ['image' => $file],
        ['image' => $request->rules()['image']],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('image'))->toBeTrue();
});

it('accepts a valid scalar user profile image', function () {
    $file = UploadedFile::fake()->image('profile.webp');
    $request = new UserForm(app(LocaleRepository::class));
    $request->initialize(files: ['image' => $file]);

    $validator = Validator::make(
        ['image' => $file],
        ['image' => $request->rules()['image']],
    );

    expect($validator->passes())->toBeTrue();
});

it('rejects image content with an active filename in the swatch API', function () {
    $file = UploadedFile::fake()->image('swatch.html');
    $rules = (new StoreSwatchMediaRequest)->rules()['file'];

    expect(Validator::make(['file' => $file], ['file' => $rules])->fails())->toBeTrue();
});
