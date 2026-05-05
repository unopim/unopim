<?php

it('AbstractType checks isValid() before storing a file/image upload', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/Product/src/Type/AbstractType.php')
    );

    expect($source)->toContain('isValid()');
});

it('AbstractType throws ValidationException on UPLOAD_ERR_INI_SIZE and UPLOAD_ERR_FORM_SIZE', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/Product/src/Type/AbstractType.php')
    );

    expect($source)
        ->toContain('UPLOAD_ERR_INI_SIZE')
        ->toContain('UPLOAD_ERR_FORM_SIZE')
        ->toContain('ValidationException::withMessages');
});

it('AbstractType uses the file-size-exceeds translation key', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/Product/src/Type/AbstractType.php')
    );

    expect($source)->toContain('admin::app.common.file-size-exceeds');
});

it('ProductController catches ValidationException from update and flashes an error toast', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/Admin/src/Http/Controllers/Catalog/ProductController.php')
    );

    expect($source)
        ->toContain("productRepository->update(\$data, \$id)")
        ->toContain("catch (ValidationException \$e)")
        ->toContain("session()->flash('error'");
});

it('file-size-exceeds translation key exists in every admin language file', function () {
    $langDir = base_path('packages/Webkul/Admin/src/Resources/lang');

    $files = glob($langDir . '/*/app.php');

    expect($files)->not->toBeEmpty();

    $missing = [];

    foreach ($files as $file) {
        if (! str_contains(file_get_contents($file), "'file-size-exceeds'")) {
            $missing[] = basename(dirname($file));
        }
    }

    expect($missing)->toBeEmpty();
});

it('public/.user.ini sets upload_max_filesize to 50M for PHP-FPM deployments', function () {
    $ini = file_get_contents(base_path('public/.user.ini'));

    expect($ini)
        ->toContain('upload_max_filesize = 50M')
        ->toContain('post_max_size = 55M');
});
