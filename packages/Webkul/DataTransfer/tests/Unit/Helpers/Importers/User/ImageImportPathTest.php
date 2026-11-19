<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\User\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->admin = Admin::factory()->create();

    $this->importer = app(Importer::class);
    $this->importer->setImport(JobTrack::factory()->create([
        'action'                => Import::ACTION_APPEND,
        'images_directory_path' => 'imports/images',
        'meta'                  => ['batch_count' => 0],
    ]));
    $this->importer->setErrorHelper(app(Error::class));

    Storage::fake('public');

    $disk = Storage::disk('public');

    $disk->put('imports/images/avatar.png', UploadedFile::fake()->image('avatar.png')->getContent());
    $disk->put('imports/images/notes.txt', 'plain text');
    $disk->put('imports/outside.png', UploadedFile::fake()->image('outside.png')->getContent());
    $disk->put('secret.txt', 'APP_KEY=secret');
});

function storeImportedUserImage(Importer $importer, ?string $image, Admin $admin): ?string
{
    return (fn (): ?string => $this->storeUserImage($image, $admin))->call($importer);
}

it('copies an image that sits inside the import images directory', function () {
    $path = storeImportedUserImage($this->importer, 'avatar.png', $this->admin);

    expect($path)->toBe('admins/'.$this->admin->id.'/avatar.png')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

it('refuses an image cell that climbs out of the import images directory', function (string $image) {
    expect(storeImportedUserImage($this->importer, $image, $this->admin))->toBeNull()
        ->and(Storage::disk('public')->allFiles('admins'))->toBe([]);
})->with([
    'parent file'        => '../outside.png',
    'disk root file'     => '../../secret.txt',
    'backslash segments' => '..\\..\\secret.txt',
    'nested climb'       => 'sub/../../outside.png',
]);

it('refuses a symlink inside the images directory that points outside it', function () {
    $disk = Storage::disk('public');

    symlink($disk->path('secret.txt'), $disk->path('imports/images/linked.png'));

    expect(storeImportedUserImage($this->importer, 'linked.png', $this->admin))->toBeNull()
        ->and($disk->allFiles('admins'))->toBe([]);
});

it('refuses a file inside the images directory that is not an image', function () {
    expect(storeImportedUserImage($this->importer, 'notes.txt', $this->admin))->toBeNull()
        ->and(Storage::disk('public')->allFiles('admins'))->toBe([]);
});
