<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Rules\FileOrImageValidValue;

function passesMediaRules(Attribute $attribute, string $method, UploadedFile $file): bool
{
    $value = $attribute->type === 'gallery' ? [$file] : $file;

    return Validator::make(['media' => $value], ['media' => $attribute->{$method}()])->passes();
}

it('never allows an executable or markup extension even when configured', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['pdf', 'html', 'php', 'phtml']]);

    expect(passesMediaRules($attribute, $method, UploadedFile::fake()->createWithContent('page.html', '<html><body>x</body></html>')))->toBeFalse()
        ->and(passesMediaRules($attribute, $method, UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')))->toBeFalse()
        ->and(passesMediaRules($attribute, $method, UploadedFile::fake()->createWithContent('shell.phtml', '<?php echo 1;')))->toBeFalse();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('uses the per-attribute max file size in the client-side size rule', function (string $type) {
    $attribute = Attribute::factory()->make(['type' => $type, 'max_file_size' => 25]);

    expect(json_decode($attribute->getValidationsField(), true)['size'])->toBe(25600);
})->with(['file', 'image']);

it('falls back to the global upload size in the client-side size rule', function (string $type) {
    $attribute = Attribute::factory()->make(['type' => $type, 'max_file_size' => null]);

    expect(json_decode($attribute->getValidationsField(), true)['size'])->toBe(2048);
})->with(['file', 'image']);

it('refuses a blocked extension even when the rule is told to allow it', function () {
    $rule = new FileOrImageValidValue(allowedMimes: ['txt', 'php'], allowedExtensions: ['txt', 'php']);

    expect(Validator::make(['media' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')], ['media' => [$rule]])->passes())->toBeFalse()
        ->and(Validator::make(['media' => UploadedFile::fake()->createWithContent('notes.txt', 'plain text')], ['media' => [$rule]])->passes())->toBeTrue();
});
