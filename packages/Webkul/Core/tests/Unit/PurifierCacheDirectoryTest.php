<?php

use Illuminate\Support\Facades\File;
use Webkul\Core\Providers\CoreServiceProvider;

beforeEach(function (): void {
    $this->path = storage_path('app/purifier-'.Str::random(8));

    $this->ensure = fn (string $path) => (new class($this->app) extends CoreServiceProvider
    {
        public function ensure(string $path): void
        {
            $this->ensurePurifierCacheDirectory($path);
        }
    })->ensure($path);
});

afterEach(function (): void {
    File::deleteDirectory($this->path);
    File::delete($this->path);
});

it('creates the purifier cache directory when it is missing', function (): void {
    expect(File::isDirectory($this->path))->toBeFalse();

    ($this->ensure)($this->path);

    expect(File::isDirectory($this->path))->toBeTrue();
});

it('is idempotent when the directory already exists', function (): void {
    File::makeDirectory($this->path, 0755, true);

    ($this->ensure)($this->path);

    expect(File::isDirectory($this->path))->toBeTrue();
});

it('treats a directory created by a concurrent boot as success', function (): void {
    $winner = fn () => File::makeDirectory($this->path, 0755, true);

    $winner();

    expect(fn () => ($this->ensure)($this->path))->not->toThrow(RuntimeException::class);
});

it('reports the underlying error when the directory cannot be created', function (): void {
    File::put($this->path, '');

    expect(fn () => ($this->ensure)($this->path))
        ->toThrow(RuntimeException::class)
        ->and(fn () => ($this->ensure)($this->path))
        ->toThrow(fn (RuntimeException $e) => expect($e->getMessage())
            ->toContain($this->path)
            ->toContain('exists'));
});
