<?php

use Webkul\Installer\Console\Commands\Installer;

describe('Installer env parsing', function () {
    $parse = fn (array $lines, string $key) => (new class extends Installer
    {
        public static function parse(array $lines, string $key): string|bool
        {
            return self::parseEnvValue($lines, $key);
        }
    })::parse($lines, $key);

    it('unquotes values and keeps inner spaces', function () use ($parse) {
        expect($parse(['APP_NAME="UnoPim master"'."\n"], 'APP_NAME'))->toBe('UnoPim master');
    });

    it('returns plain and empty values', function () use ($parse) {
        expect($parse(["ELASTICSEARCH_INDEX_PREFIX=\n", "DB_HOST=127.0.0.1\n"], 'ELASTICSEARCH_INDEX_PREFIX'))->toBe('')
            ->and($parse(["DB_HOST=127.0.0.1\n"], 'DB_HOST'))->toBe('127.0.0.1');
    });

    it('does not match keys by substring and returns false when missing', function () use ($parse) {
        expect($parse(["DB_HOST_X=a\n"], 'DB_HOST'))->toBeFalse();
    });
});
