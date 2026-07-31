<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Helpers\Upgrade\BackupManager;
use Webkul\Installer\Helpers\Upgrade\CheckResult;
use Webkul\Installer\Helpers\Upgrade\DriftReporter;
use Webkul\Installer\Helpers\Upgrade\MigrationSizer;
use Webkul\Installer\Helpers\Upgrade\PostUpgradeVerifier;
use Webkul\Installer\Helpers\Upgrade\PreflightChecker;

/**
 * Upgrades an existing installation to the release this command ships in.
 *
 * Run it from the new release directory while the previous one is still
 * serving: phases 1 to 3 only read, so a failed check leaves the site up and
 * untouched. Only phase 4 mutates, and it does so behind maintenance mode with
 * a verified database dump already on disk.
 */
#[Description('Upgrade an existing UnoPim installation to this release.')]
#[Signature('unopim:upgrade
        { --from= : Path to the previous release directory, used to report configuration drift. }
        { --dry-run : Run the checks and print the maintenance estimate without changing anything. }
        { --with-reindex : Rebuild Elasticsearch indexes inline instead of deferring them. }
        { --skip-backup : Skip the database dump. Only for installations backed up by infrastructure. }
        { --force : Continue even when the installed release is older than the supported floor. }')]
class Upgrade extends Command
{
    public function __construct(
        protected PreflightChecker $preflight,
        protected DriftReporter $drift,
        protected MigrationSizer $sizer,
        protected BackupManager $backup,
        protected PostUpgradeVerifier $verifier,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->title(trans('installer::app.upgrade.title'));

        if (! $this->runPreflight()) {
            return self::FAILURE;
        }

        $this->reportDrift();

        $this->reportSizing();

        if ($this->option('dry-run')) {
            $this->info(trans('installer::app.upgrade.dry-run-complete'));

            return self::SUCCESS;
        }

        if (! $this->confirmToProceed()) {
            $this->warn(trans('installer::app.upgrade.aborted'));

            return self::FAILURE;
        }

        return $this->runUpgrade();
    }

    /**
     * Phase 1. Nothing has been touched when this returns false.
     */
    protected function runPreflight(): bool
    {
        $this->title(trans('installer::app.upgrade.phase.preflight'));

        $results = $this->preflight->run((bool) $this->option('force'));

        $this->renderResults($results);

        $failures = array_filter($results, fn (CheckResult $result): bool => $result->isFailure());

        if ($failures === []) {
            return true;
        }

        $this->newLine();
        $this->error(trans('installer::app.upgrade.preflight-failed', ['count' => count($failures)]));
        $this->line(trans('installer::app.upgrade.preflight-failed-hint'));

        return false;
    }

    /**
     * Phase 2. Reports only; customer-owned files are never rewritten here.
     */
    protected function reportDrift(): void
    {
        $this->title(trans('installer::app.upgrade.phase.drift'));

        $report = $this->drift->from($this->option('from'))->report();

        if (! $report['compared']) {
            $this->line(trans('installer::app.upgrade.drift.no-previous'));
        }

        if (! $this->drift->hasDrift($report)) {
            $this->info(trans('installer::app.upgrade.drift.none'));

            return;
        }

        $this->renderList(trans('installer::app.upgrade.drift.env-missing'), $report['env']['missing']);
        $this->renderList(trans('installer::app.upgrade.drift.env-removed'), $report['env']['removed']);
        $this->renderList(trans('installer::app.upgrade.drift.config'), $report['config']);
        $this->renderList(trans('installer::app.upgrade.drift.composer'), $report['composer']);

        $this->newLine();
        $this->warn(trans('installer::app.upgrade.drift.manual'));
    }

    /**
     * Phase 3. Sizes the maintenance window before any downtime is booked.
     */
    protected function reportSizing(): void
    {
        $this->title(trans('installer::app.upgrade.phase.sizing'));

        $estimate = $this->sizer->estimate();

        if ($estimate['pending'] === []) {
            $this->info(trans('installer::app.upgrade.sizing.nothing-pending'));

            return;
        }

        $rows = [];

        foreach ($estimate['tables'] as $table => $count) {
            $rows[] = [$table, number_format($count)];
        }

        if ($rows !== []) {
            $this->table([
                trans('installer::app.upgrade.sizing.table'),
                trans('installer::app.upgrade.sizing.rows'),
            ], $rows);
        }

        $irreversible = array_column(array_filter($estimate['pending'], fn (array $migration): bool => $migration['irreversible']), 'name');

        $this->line(trans('installer::app.upgrade.sizing.pending', ['count' => count($estimate['pending'])]));
        $this->line(trans('installer::app.upgrade.sizing.window', ['minutes' => max(1, (int) ceil($estimate['seconds'] / 60))]));

        if ($irreversible !== []) {
            $this->newLine();
            $this->warn(trans('installer::app.upgrade.sizing.irreversible'));
            $this->renderList('', $irreversible);
        }
    }

    /**
     * Phase 4 and 5. The only mutating path.
     */
    protected function runUpgrade(): int
    {
        $this->title(trans('installer::app.upgrade.phase.execute'));

        if (! $this->option('skip-backup')) {
            try {
                $path = $this->backup->dump();

                $this->info(trans('installer::app.upgrade.backup.created', ['path' => $path]));
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                $this->line(trans('installer::app.upgrade.backup.abort'));

                return self::FAILURE;
            }
        } else {
            $this->warn(trans('installer::app.upgrade.backup.skipped'));
        }

        $this->call('down', ['--render' => 'errors::503']);

        try {
            $this->runStep('migrate', ['--force' => true]);

            $this->runStep('storage:link');

            $this->runStep('optimize:clear');

            $this->runStep('queue:restart');
        } catch (\Throwable $e) {
            $this->error(trans('installer::app.upgrade.migrate-failed', ['error' => $e->getMessage()]));

            $this->renderRestoreRecipe();

            return self::FAILURE;
        }

        if (! $this->runVerification()) {
            $this->renderRestoreRecipe();

            return self::FAILURE;
        }

        $this->call('up');

        $this->reportReindex();

        $this->newLine();
        $this->info(trans('installer::app.upgrade.complete'));

        return self::SUCCESS;
    }

    /**
     * Run one migration-phase command, failing closed on its exit code.
     *
     * Artisan only propagates an exception for the errors that throw; a command
     * that simply returns a non-zero code is otherwise invisible here, and
     * continuing past one would bring the site back up on a half-migrated
     * schema.
     *
     * @param  array<string, mixed>  $arguments
     *
     * @throws \RuntimeException
     */
    protected function runStep(string $command, array $arguments = []): void
    {
        $code = $this->call($command, $arguments);

        if ($code !== self::SUCCESS) {
            throw new \RuntimeException(trans('installer::app.upgrade.step-failed', [
                'command' => $command,
                'code'    => $code,
            ]));
        }
    }

    /**
     * Phase 5. A failure here deliberately leaves maintenance mode engaged.
     */
    protected function runVerification(): bool
    {
        $this->title(trans('installer::app.upgrade.phase.verify'));

        $results = $this->verifier->run();

        $this->renderResults($results);

        $failures = array_filter($results, fn (CheckResult $result): bool => $result->isFailure());

        if ($failures === []) {
            return true;
        }

        $this->newLine();
        $this->error(trans('installer::app.upgrade.verify-failed', ['count' => count($failures)]));

        return false;
    }

    /**
     * Elasticsearch is rebuilt after cutover by default: a full reindex on a
     * large catalog runs far longer than the schema migration, and search
     * degrades to the database in the meantime rather than failing.
     */
    protected function reportReindex(): void
    {
        if (! filter_var(config('elasticsearch.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $products = DB::table('products')->count();

        $limit = (int) config('upgrade.inline_reindex_product_limit');

        if ($this->option('with-reindex') && $products <= $limit) {
            $this->call('unopim:elastic:clear');
            $this->call('unopim:category:index');
            $this->call('unopim:product:index');

            return;
        }

        $this->newLine();

        if ($this->option('with-reindex')) {
            $this->warn(trans('installer::app.upgrade.reindex.too-large', [
                'count' => number_format($products),
                'limit' => number_format($limit),
            ]));
        }

        $this->warn(trans('installer::app.upgrade.reindex.deferred', ['count' => number_format($products)]));

        foreach (['unopim:elastic:clear', 'unopim:category:index', 'unopim:product:index'] as $command) {
            $this->line('  php artisan '.$command);
        }
    }

    protected function renderRestoreRecipe(): void
    {
        $this->newLine();
        $this->error(trans('installer::app.upgrade.restore.heading'));
        $this->line(trans('installer::app.upgrade.restore.body'));
    }

    protected function confirmToProceed(): bool
    {
        return ! $this->input->isInteractive()
            || $this->confirm(trans('installer::app.upgrade.confirm'), false);
    }

    /**
     * @param  array<int, CheckResult>  $results
     */
    protected function renderResults(array $results): void
    {
        foreach ($results as $result) {
            $this->line(sprintf(
                '  <%s>%s</> %s%s',
                $result->status->style(),
                $result->status->glyph(),
                $result->name,
                $result->detail !== '' ? ' — '.$result->detail : ''
            ));

            if ($result->remedy !== '') {
                $this->line('      <comment>'.$result->remedy.'</comment>');
            }
        }
    }

    /**
     * @param  array<int, string>  $items
     */
    protected function renderList(string $heading, array $items): void
    {
        if ($items === []) {
            return;
        }

        $this->newLine();

        if ($heading !== '') {
            $this->line('<comment>'.$heading.'</comment>');
        }

        foreach ($items as $item) {
            $this->line('  • '.$item);
        }
    }

    protected function title(string $text): void
    {
        $this->newLine();
        $this->line('<options=bold>'.$text.'</>');
    }
}
