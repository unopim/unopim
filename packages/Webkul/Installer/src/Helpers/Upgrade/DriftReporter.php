<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Support\Facades\File;

/**
 * Reports what an operator still has to reconcile by hand after dropping a new
 * release beside the old one: environment keys, published configuration, and
 * self-added Composer requirements.
 *
 * Nothing here writes. These files carry local decisions the upgrade has no
 * basis to overrule, and silently merging them is how customisations get lost.
 *
 * With a previous release directory the comparison is exact — old tree against
 * new tree. Without one it degrades to comparing the live `.env` against
 * `.env.example` and published config against package defaults, which still
 * catches new and retired keys.
 */
class DriftReporter
{
    protected ?string $previousPath = null;

    public function from(?string $previousPath): self
    {
        $this->previousPath = $previousPath !== null ? rtrim($previousPath, DIRECTORY_SEPARATOR) : null;

        return $this;
    }

    /**
     * @return array{env: array{missing: array<int, string>, removed: array<int, string>}, config: array<int, string>, composer: array<int, string>, compared: bool}
     */
    public function report(): array
    {
        return [
            'env'      => $this->envDrift(),
            'config'   => $this->configDrift(),
            'composer' => $this->composerDrift(),
            'compared' => $this->previousPath !== null,
        ];
    }

    public function hasDrift(array $report): bool
    {
        return $report['env']['missing'] !== []
            || $report['env']['removed'] !== []
            || $report['config'] !== []
            || $report['composer'] !== [];
    }

    /**
     * Keys the release added, and keys it retired that are still set locally.
     *
     * @return array{missing: array<int, string>, removed: array<int, string>}
     */
    protected function envDrift(): array
    {
        $current = $this->envKeys($this->previousPath !== null
            ? $this->previousPath.'/.env'
            : base_path('.env'));

        $shipped = $this->envKeys(base_path('.env.example'));

        return [
            'missing' => array_values(array_diff($shipped, $current)),
            'removed' => array_values(array_intersect((array) config('upgrade.removed_env_keys'), $current)),
        ];
    }

    /**
     * Config files carrying local edits.
     *
     * @return array<int, string>
     */
    protected function configDrift(): array
    {
        $modified = [];

        foreach ($this->configComparisons() as $relative => [$mine, $theirs]) {
            if (! File::exists($mine) || ! File::exists($theirs)) {
                continue;
            }

            if (File::hash($mine) !== File::hash($theirs)) {
                $modified[] = $relative;
            }
        }

        sort($modified);

        return $modified;
    }

    /**
     * Requirements declared in the previous release but absent from this one —
     * packages the operator added and must re-declare in the new tree.
     *
     * @return array<int, string>
     */
    protected function composerDrift(): array
    {
        if ($this->previousPath === null) {
            return [];
        }

        $previous = $this->composerRequirements($this->previousPath.'/composer.json');

        $current = $this->composerRequirements(base_path('composer.json'));

        if ($previous === []) {
            return [];
        }

        return array_values(array_diff(array_keys($previous), array_keys($current)));
    }

    /**
     * Config files to diff, keyed by their project-relative path.
     *
     * Only meaningful against a previous release: an application's `config/`
     * is expected to differ from the package defaults it was published from,
     * so comparing the two would report every file as drifted.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    protected function configComparisons(): array
    {
        if ($this->previousPath === null) {
            return [];
        }

        $comparisons = [];

        foreach (File::glob($this->previousPath.'/config/*.php') as $mine) {
            $comparisons['config/'.basename($mine)] = [$mine, config_path(basename($mine))];
        }

        return $comparisons;
    }

    /**
     * @return array<int, string>
     */
    protected function envKeys(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $keys = [];

        foreach (preg_split('/\R/', File::get($path)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^([A-Z0-9_]+)\s*=/', $line, $matches) === 1) {
                $keys[] = $matches[1];
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return array<string, string>
     */
    protected function composerRequirements(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) && is_array($decoded['require'] ?? null) ? $decoded['require'] : [];
    }
}
