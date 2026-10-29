<?php

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\Installer\Console\Commands\Installer;

function confirmDroppingTablesUnattended(array $tables, string $tablePrefix, array $options = []): array
{
    $command = app(Installer::class);

    $input = new ArrayInput($options, $command->getDefinition());
    $input->setInteractive(false);

    $buffer = new BufferedOutput;

    $command->setInput($input);
    $command->setOutput(new OutputStyle($input, $buffer));

    $confirmed = (new ReflectionMethod($command, 'confirmDroppingTables'))->invoke($command, $tables, $tablePrefix);

    return [$confirmed, $buffer->fetch()];
}

it('refuses an unattended drop without a table prefix unless --force is passed', function () {
    [$confirmed, $output] = confirmDroppingTablesUnattended(['admins', 'legacy_app_users'], '');

    expect($confirmed)->toBeFalse()
        ->and($output)->toContain('admins, legacy_app_users')
        ->and($output)->toContain(trans('installer::app.installer.console.force-required'));
});

it('drops without a table prefix when --force is passed', function () {
    [$confirmed, $output] = confirmDroppingTablesUnattended(['admins', 'legacy_app_users'], '', ['--force' => true]);

    expect($confirmed)->toBeTrue()
        ->and($output)->toContain('admins, legacy_app_users')
        ->and($output)->not->toContain(trans('installer::app.installer.console.force-required'));
});

it('drops the prefixed tables of an unattended run without --force', function () {
    [$confirmed, $output] = confirmDroppingTablesUnattended(['3X_admins', '3X_products'], '3X_');

    expect($confirmed)->toBeTrue()
        ->and($output)->toContain('3X_admins, 3X_products');
});

it('has nothing to confirm when no tables would be dropped', function () {
    [$confirmed, $output] = confirmDroppingTablesUnattended([], '');

    expect($confirmed)->toBeTrue()
        ->and($output)->toBe('');
});
