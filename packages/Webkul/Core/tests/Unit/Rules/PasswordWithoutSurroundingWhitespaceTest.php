<?php

use Webkul\Core\Rules\PasswordWithoutSurroundingWhitespace;

function passwordRuleFails(mixed $value, array $extra = ['nullable']): bool
{
    return validator(
        ['password' => $value],
        ['password' => [...$extra, new PasswordWithoutSurroundingWhitespace]]
    )->fails();
}

it('rejects surrounding whitespace', function (string $password) {
    expect(passwordRuleFails($password))->toBeTrue();
})->with([
    'leading space'    => ['   NewPassw0rd'],
    'trailing space'   => ['NewPassw0rd   '],
    'leading tab'      => ["\tNewPassw0rd"],
    'trailing newline' => ["NewPassw0rd\n"],
    'whitespace only'  => ['          '],
]);

it('accepts passwords without surrounding whitespace', function (string $password) {
    expect(passwordRuleFails($password))->toBeFalse();
})->with([
    'plain'         => ['NewPassw0rd'],
    'inner spaces'  => ['correct horse battery staple'],
    'inner tab'     => ["New\tPassw0rd"],
    'unicode'       => ['pässwörd-123'],
]);

it('leaves an absent password to the other rules', function () {
    expect(passwordRuleFails(null))->toBeFalse()
        ->and(passwordRuleFails(''))->toBeFalse();
});

it('runs even when the value trims to empty', function () {
    expect((new PasswordWithoutSurroundingWhitespace)->implicit)->toBeTrue();
});
