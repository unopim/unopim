<?php

use Illuminate\Console\ManuallyFailedException;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webkul\Installer\Console\Commands\Installer;
use Webkul\Installer\Helpers\DatabaseManager;

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

function dropExistingTablesWithoutPrefix(DatabaseManager $databaseManager, array $options = []): void
{
    app()->instance(DatabaseManager::class, $databaseManager);

    $command = app(Installer::class);

    $input = new ArrayInput($options, $command->getDefinition());
    $input->setInteractive(false);

    $command->setInput($input);
    $command->setOutput(new OutputStyle($input, new BufferedOutput));

    $connection = DB::connection();
    $tablePrefix = $connection->getTablePrefix();
    $connection->setTablePrefix('');

    try {
        (new ReflectionMethod($command, 'dropExistingTables'))->invoke($command);
    } finally {
        $connection->setTablePrefix($tablePrefix);
    }
}

it('fails the install command without dropping anything when the unattended drop is refused', function () {
    $databaseManager = Mockery::mock(DatabaseManager::class);
    $databaseManager->shouldReceive('currentPrefixTables')->once()->andReturn(['admins', 'legacy_app_users']);
    $databaseManager->shouldNotReceive('dropCurrentPrefixTables');

    expect(fn () => dropExistingTablesWithoutPrefix($databaseManager))
        ->toThrow(ManuallyFailedException::class, trans('installer::app.installer.console.drop-aborted'));
});

it('drops the tables without a table prefix when --force is passed', function () {
    $databaseManager = Mockery::mock(DatabaseManager::class);
    $databaseManager->shouldReceive('currentPrefixTables')->once()->andReturn(['admins', 'legacy_app_users']);
    $databaseManager->shouldReceive('dropCurrentPrefixTables')->once();

    dropExistingTablesWithoutPrefix($databaseManager, ['--force' => true]);
});
