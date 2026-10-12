<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Database\Migrations\Migrator;

/**
 * Read-only view over the migration repository, shared by the preflight,
 * sizing and verification stages.
 */
class MigrationInspector
{
    /**
     * @var array<int, string>|null
     */
    protected ?array $ran = null;

    public function __construct(protected Migrator $migrator) {}

    /**
     * Migration names already recorded as run.
     *
     * @return array<int, string>
     */
    public function ran(): array
    {
        if ($this->ran !== null) {
            return $this->ran;
        }

        if (! $this->migrator->repositoryExists()) {
            return $this->ran = [];
        }

        return $this->ran = $this->migrator->getRepository()->getRan();
    }

    public function hasRun(string $migration): bool
    {
        return in_array($migration, $this->ran(), true);
    }

    /**
     * Migration names present on disk but not yet recorded as run.
     *
     * @return array<int, string>
     */
    public function pending(): array
    {
        $files = $this->migrator->getMigrationFiles($this->paths());

        $names = array_map($this->migrator->getMigrationName(...), $files);

        return array_values(array_diff($names, $this->ran()));
    }

    /**
     * Every path the migrator will read from.
     *
     * `Migrator::paths()` only reports paths registered by packages, so the
     * application's own migration directory has to be added back or the whole
     * framework migration set reads as pending.
     *
     * @return array<int, string>
     */
    protected function paths(): array
    {
        return array_values(array_unique([...$this->migrator->paths(), database_path('migrations')]));
    }
}
