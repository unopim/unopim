<?php

use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Webkul\Installer\Console\Commands\Installer;
use Webkul\Installer\Console\Prompts\PreselectedSearchPrompt;

function installerLocalePrompt(?string $default): PreselectedSearchPrompt
{
    $locales = [
        'en_US' => 'English (United States)',
        'fr_FR' => 'French (France)',
        'de_DE' => 'German (Germany)',
    ];

    return new PreselectedSearchPrompt(
        label: 'Please select the default application locale',
        options: function (string $value) use ($locales) {
            $value = strtolower(trim($value));

            if ($value === '') {
                return $locales;
            }

            return array_filter(
                $locales,
                fn (string $label, string $key) => str_contains(strtolower($label), $value)
                    || str_contains(strtolower($key), $value),
                ARRAY_FILTER_USE_BOTH
            );
        },
        scroll: 10,
        defaultValue: $default,
    );
}

afterEach(function () {
    Prompt::interactive(false);
});

describe('PreselectedSearchPrompt', function () {
    it('pre-selects the existing .env value and keeps it when Enter is pressed', function () {
        PreselectedSearchPrompt::fake([Key::ENTER]);

        expect(installerLocalePrompt('en_US')->prompt())->toBe('en_US');
    });

    it('lets the user clear the default with Backspace and search a different value', function () {
        PreselectedSearchPrompt::fake([
            Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE, Key::BACKSPACE,
            'fr_FR',
            Key::DOWN,
            Key::ENTER,
        ]);

        expect(installerLocalePrompt('en_US')->prompt())->toBe('fr_FR');
    });

    it('shows an empty searchable prompt when no default is configured', function () {
        PreselectedSearchPrompt::fake(['fr_FR', Key::DOWN, Key::ENTER]);

        expect(installerLocalePrompt(null)->prompt())->toBe('fr_FR');
    });

    it('falls back to an empty prompt when the .env value is no longer a valid option', function () {
        PreselectedSearchPrompt::fake(['fr_FR', Key::DOWN, Key::ENTER]);

        expect(installerLocalePrompt('zz_ZZ')->prompt())->toBe('fr_FR');
    });
});

describe('Installer::getEnvChoiceDefault', function () {
    $resolve = function (string $envValue, array $choices): ?string {
        $cmd = new class extends Installer
        {
            public static string $stubValue = '';

            protected static function getEnvAtRuntime(string $key): string|bool
            {
                return self::$stubValue;
            }
        };

        $cmd::$stubValue = $envValue;

        return (new ReflectionMethod($cmd, 'getEnvChoiceDefault'))
            ->invoke($cmd, 'APP_LOCALE', $choices);
    };

    it('returns the existing value when it is a valid option', function () use ($resolve) {
        expect($resolve('en_US', ['en_US' => 'English (United States)']))->toBe('en_US');
    });

    it('returns null when the existing value is not a valid option', function () use ($resolve) {
        expect($resolve('zz_ZZ', ['en_US' => 'English (United States)']))->toBeNull();
    });

    it('returns null when no value is configured', function () use ($resolve) {
        expect($resolve('', ['en_US' => 'English (United States)']))->toBeNull();
    });

    it('strips surrounding quotes before matching options', function () use ($resolve) {
        expect($resolve('"en_US"', ['en_US' => 'English (United States)']))->toBe('en_US');
    });
});
