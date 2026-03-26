<?php

declare(strict_types=1);

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Audits translation file integrity across all UnoPim packages.
 *
 * Ensures every locale directory mirrors the canonical (en_US) structure:
 * same files, same nested keys, consistent placeholders, no orphans.
 */
class TranslationsChecker extends Command
{
    protected $signature = 'unopim:translations:check
        {--locale= : Audit a single locale against the canonical locale.}
        {--package= : Restrict audit to one package.}
        {--missing-in-code : Detect translation keys referenced in code but absent from lang files.}
        {--unused : Detect lang keys that no source file references.}
        {--fix : Reconcile every locale with the canonical locale (add absent keys, prune orphans).}
        {--details : Emit granular diagnostics per file and key.}
        {--placeholder-check : Verify translation placeholder consistency (:name, :count, etc.).}
        {--empty-values : Detect keys with empty or blank string values.}
        {--untranslated : Find keys where locale value is identical to en_US (likely not translated).}
        {--coverage : Show translation coverage percentage per locale.}
        {--json : Output results as machine-readable JSON for CI pipelines.}
        {--sort-check : Verify keys follow same ordering as canonical file.}
        {--html-check : Verify HTML tag consistency between translations.}
        {--strict : Enable all quality checks (placeholder, empty, untranslated, sort, html).}
        {--translate : Use AI to translate absent keys instead of copying English values (requires --fix).}
        {--fix-untranslated : Re-translate keys where locale value is identical to en_US (requires --fix --translate).}
        {--fallback : When AI translation is unavailable, fall back to copying English values instead of aborting.}';

    protected $description = 'Audit translation file integrity across all UnoPim packages (en_US is canonical).';

    private const CANONICAL_LOCALE = 'en_US';

    private const DIAGNOSTIC_CAP = 10;

    private const PACKAGE_ROOTS = ['packages/Webkul'];

    private const LANG_CANDIDATES = ['/src/Resources/lang', '/resources/lang'];

    private const SOURCE_DIRS = ['/src', '/resources', '/Config', '/Routes'];

    /** Minimum character length to flag as untranslated (skip "ID", "OK", etc.). */
    private const UNTRANSLATED_MIN_LENGTH = 3;

    /** Single-word values and technical terms are often legitimately identical across languages. */
    private const UNTRANSLATED_SKIP_PATTERNS = [
        '/^[A-Z][a-z]*$/',                // Single capitalized word (e.g., "Type", "Image", "Simple")
        '/^[A-Z]+$/',                      // Acronyms (e.g., "SKU", "API", "URL", "CSV")
        '/^[A-Z][\w]* [A-Z][\w]*$/',      // Two capitalized words (e.g., "Magic AI", "Data Grid")
        '/^[\w\-.:\/]+$/',                 // Technical tokens without spaces (e.g., "webhook", "app.php")
        '/^[A-Z][\w\s]*:\s*:[a-z_]+$/',   // Label with placeholder (e.g., "Version : :version")
        '/^[A-Z]+\s*[\-–]\s*:[a-z_]+$/',  // Acronym with placeholder (e.g., "SKU - :sku")
        '/^[\d.]+\s*(MB|GB|KB|TB)$/',      // File sizes (e.g., "10 MB")
        '/^:[\w]+$/',                       // Bare placeholders (e.g., ":name")
        '/^[\w]+(?:,\s*[\w]+)+$/',          // Comma-separated technical lists (e.g., "png, jpeg, jpg")
        '/^[A-Z][\w.]*(?:\s+[\dA-Z][\w.]*)*$/', // Product/model names with numbers (e.g., "Llama 2 13B")
    ];

    /** Map locale codes to human-readable language names for AI translation prompts. */
    private const LOCALE_NAMES = [
        'ar_AE' => 'Arabic',
        'ca_ES' => 'Catalan',
        'da_DK' => 'Danish',
        'de_DE' => 'German',
        'en_AU' => 'English (Australia)',
        'en_GB' => 'English (United Kingdom)',
        'en_NZ' => 'English (New Zealand)',
        'es_ES' => 'Spanish',
        'es_VE' => 'Spanish (Venezuela)',
        'fi_FI' => 'Finnish',
        'fr_FR' => 'French',
        'hi_IN' => 'Hindi',
        'hr_HR' => 'Croatian',
        'id_ID' => 'Indonesian',
        'it_IT' => 'Italian',
        'ja_JP' => 'Japanese',
        'ko_KR' => 'Korean',
        'mn_MN' => 'Mongolian',
        'nl_NL' => 'Dutch',
        'no_NO' => 'Norwegian',
        'pl_PL' => 'Polish',
        'pt_BR' => 'Brazilian Portuguese',
        'pt_PT' => 'Portuguese',
        'ro_RO' => 'Romanian',
        'ru_RU' => 'Russian',
        'sv_SE' => 'Swedish',
        'tl_PH' => 'Filipino',
        'tr_TR' => 'Turkish',
        'uk_UA' => 'Ukrainian',
        'vi_VN' => 'Vietnamese',
        'zh_CN' => 'Simplified Chinese',
        'zh_TW' => 'Traditional Chinese',
    ];

    private const KEY_PATTERNS = [
        '/@lang\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/',
        '/(?<!\w)trans\s*\(\s*[\'"]([^\'"]+)[\'"]\s*[,\)]/',
        '/(?<!\w)__\s*\(\s*[\'"]([^\'"]+)[\'"]\s*[,\)]/',
    ];

    /** Finding kinds that cause the audit to fail (exit code 1). */
    private const ERROR_KINDS = [
        'absent_files', 'orphan_files', 'absent_keys', 'orphan_keys',
        'parse_failures', 'placeholder_mismatches', 'html_mismatches',
    ];

    /** Finding kinds that are informational warnings (do not fail the audit). */
    private const WARNING_KINDS = [
        'empty_values', 'untranslated', 'sort_violations',
    ];

    private bool $auditFailed = false;

    private Collection $auditRows;

    private Collection $diagnostics;

    public function __construct()
    {
        parent::__construct();

        $this->auditRows = collect();
        $this->diagnostics = collect();
    }

    // ─── Entry Point ─────────────────────────────────────────────

    public function handle(): int
    {
        $locale = $this->option('locale');
        $pkg = $this->option('package');
        $verbose = $this->option('details');
        $jsonOutput = $this->option('json');
        $strict = $this->option('strict');

        if ($this->option('translate') && ! $this->option('fix')) {
            $this->error('The --translate flag requires --fix. Usage: --fix --translate');

            return self::FAILURE;
        }

        if ($this->option('fix-untranslated') && (! $this->option('fix') || ! $this->option('translate'))) {
            $this->error('The --fix-untranslated flag requires --fix --translate. Usage: --fix --translate --fix-untranslated');

            return self::FAILURE;
        }

        if ($this->option('fix')) {
            return $this->reconcileLocales($pkg, $locale, $this->option('translate'), $this->option('fix-untranslated'));
        }

        $checks = $this->resolveActiveChecks($strict);

        if (! $jsonOutput) {
            $this->printBanner($locale, $pkg, $checks);
        }

        $this->auditAllPackages($pkg, $locale, $checks);

        if ($jsonOutput) {
            $this->outputJson($checks);
        } else {
            $this->renderAuditReport($verbose);

            if ($this->option('coverage')) {
                $this->renderCoverageTable();
            }
        }

        if (($this->option('missing-in-code') || $this->option('unused')) && ! $jsonOutput) {
            $this->analyseSourceCode($pkg, $this->option('missing-in-code'), $this->option('unused'), $verbose);
        }

        return $this->auditFailed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Determine which quality checks are active based on flags.
     */
    private function resolveActiveChecks(bool $strict): array
    {
        $checks = ['keys']; // always on

        $map = [
            'placeholder-check' => 'placeholders',
            'empty-values'      => 'empty_values',
            'untranslated'      => 'untranslated',
            'sort-check'        => 'sort_order',
            'html-check'        => 'html_tags',
        ];

        foreach ($map as $option => $label) {
            if ($strict || $this->option($option)) {
                $checks[] = $label;
            }
        }

        return $checks;
    }

    // ─── Banner ──────────────────────────────────────────────────

    private function printBanner(?string $locale, ?string $pkg, array $checks): void
    {
        $this->newLine();
        $this->info('UnoPim — Translation Integrity Audit');
        $this->line('   Canonical locale: <fg=cyan>'.self::CANONICAL_LOCALE.'</>');
        $this->line('   Active checks: <fg=white>'.implode(', ', $checks).'</>');

        if ($locale) {
            $this->line("   Scope → locale: <fg=yellow>{$locale}</>");
        }

        if ($pkg) {
            $this->line("   Scope → package: <fg=yellow>{$pkg}</>");
        }

        $this->newLine();
    }

    // ─── Core Audit ──────────────────────────────────────────────

    private function auditAllPackages(?string $onlyPkg, ?string $onlyLocale, array $checks): void
    {
        $this->discoverPackages($onlyPkg)->each(function (string $dir) use ($onlyLocale, $checks) {
            $langDir = $this->detectLangDir($dir);

            if ($langDir === null) {
                return;
            }

            $canonicalDir = $langDir.'/'.self::CANONICAL_LOCALE;

            if (! File::isDirectory($canonicalDir)) {
                return;
            }

            $pkg = basename($dir);
            $canonicalFiles = $this->collectPhpFiles($canonicalDir);

            if ($canonicalFiles->isEmpty()) {
                return;
            }

            $canonicalRel = $canonicalFiles->map(fn (string $f) => Str::after($f, $canonicalDir.'/'));

            $this->listNonCanonicalLocales($langDir, $onlyLocale)
                ->each(function (string $locale) use ($pkg, $langDir, $canonicalDir, $canonicalRel, $checks) {
                    $row = $this->auditLocale($pkg, $langDir, $canonicalDir, $canonicalRel, $locale, $checks);
                    $this->auditRows->push($row);

                    if ($row['verdict'] === 'fault') {
                        $this->auditFailed = true;
                    }
                });
        });
    }

    private function auditLocale(
        string $pkg,
        string $langDir,
        string $canonicalDir,
        Collection $canonicalRel,
        string $locale,
        array $checks,
    ): array {
        $localeDir = "{$langDir}/{$locale}";
        $findings = [];
        $totalKeys = 0;
        $presentKeys = 0;

        // Store langDir for building full paths in output
        $langDirRel = Str::after($langDir, base_path().'/');

        // ── File-level ───────────────────────────────────────
        $absentFiles = $canonicalRel->filter(fn (string $r) => ! File::exists("{$localeDir}/{$r}"))->values();

        if ($absentFiles->isNotEmpty()) {
            $findings['absent_files'] = $absentFiles->all();
            $this->diagnostics->push(['package' => $pkg, 'locale' => $locale, 'kind' => 'absent_files', 'lang_dir' => $langDirRel, 'files' => $absentFiles->all()]);
        }

        $localeRel = $this->collectPhpFiles($localeDir)->map(fn (string $f) => Str::after($f, $localeDir.'/'));
        $orphanFiles = $localeRel->diff($canonicalRel)->values();

        if ($orphanFiles->isNotEmpty()) {
            $findings['orphan_files'] = $orphanFiles->all();
            $this->diagnostics->push(['package' => $pkg, 'locale' => $locale, 'kind' => 'orphan_files', 'lang_dir' => $langDirRel, 'files' => $orphanFiles->all()]);
        }

        // ── Per-file analysis ────────────────────────────────
        $canonicalRel->each(function (string $rel) use ($pkg, $locale, $canonicalDir, $localeDir, $langDirRel, $checks, &$findings, &$totalKeys, &$presentKeys) {
            $cFile = "{$canonicalDir}/{$rel}";
            $lFile = "{$localeDir}/{$rel}";

            try {
                $cTree = $this->loadLangArray($cFile);
            } catch (Throwable) {
                return;
            }

            $cFlat = $this->dotKeys($cTree);
            $totalKeys += count($cFlat);

            if (! File::exists($lFile)) {
                return;
            }

            try {
                $lTree = $this->loadLangArray($lFile);
            } catch (Throwable) {
                $findings['parse_failures'][] = $rel;

                return;
            }

            $lFlat = $this->dotKeys($lTree);
            $presentKeys += count(array_intersect_key($lFlat, $cFlat));

            // Key check (always active)
            $this->checkKeys($pkg, $locale, $rel, $cFile, $lFile, $langDirRel, $findings);

            // Quality checks
            if (in_array('placeholders', $checks)) {
                $this->checkPlaceholders($pkg, $locale, $rel, $lFile, $langDirRel, $cTree, $lTree, $findings);
            }

            if (in_array('empty_values', $checks)) {
                $this->checkEmptyValues($pkg, $locale, $rel, $lFile, $langDirRel, $cTree, $lTree, $findings);
            }

            if (in_array('untranslated', $checks)) {
                $this->checkUntranslated($pkg, $locale, $rel, $lFile, $langDirRel, $cTree, $lTree, $findings);
            }

            if (in_array('sort_order', $checks)) {
                $this->checkSortOrder($pkg, $locale, $rel, $cFile, $lFile, $langDirRel, $findings);
            }

            if (in_array('html_tags', $checks)) {
                $this->checkHtmlTags($pkg, $locale, $rel, $lFile, $langDirRel, $cTree, $lTree, $findings);
            }
        });

        $coverage = $totalKeys > 0 ? round(($presentKeys / $totalKeys) * 100, 1) : 100.0;

        $untranslatedCount = count($findings['untranslated'] ?? []);
        $translatedKeys = $presentKeys - $untranslatedCount;
        $translationRate = $presentKeys > 0 ? round(($translatedKeys / $presentKeys) * 100, 1) : 100.0;

        $hasErrors = collect($findings)->keys()->intersect(self::ERROR_KINDS)->isNotEmpty();
        $hasWarnings = collect($findings)->keys()->intersect(self::WARNING_KINDS)->isNotEmpty();

        $verdict = $hasErrors ? 'fault' : ($hasWarnings ? 'warn' : 'ok');

        return [
            'package'          => $pkg,
            'locale'           => $locale,
            'verdict'          => $verdict,
            'summary'          => $this->condenseSummary($findings),
            'total_keys'       => $totalKeys,
            'present'          => $presentKeys,
            'coverage'         => $coverage,
            'translated'       => $translatedKeys,
            'untranslated'     => $untranslatedCount,
            'translation_rate' => $translationRate,
        ];
    }

    // ─── Key Check ───────────────────────────────────────────────

    private function checkKeys(string $pkg, string $locale, string $rel, string $cFile, string $lFile, string $langDirRel, array &$findings): void
    {
        $cMap = $this->dotKeysWithLines($cFile);
        $lMap = $this->dotKeysWithLines($lFile);
        $cKeys = collect($cMap)->keys();
        $lKeys = collect($lMap)->keys();

        $absent = $cKeys->diff($lKeys);
        $orphan = $lKeys->diff($cKeys);

        if ($absent->isNotEmpty()) {
            $data = $absent->mapWithKeys(fn (string $k) => [$k => $cMap[$k] ?? null])->all();
            $findings['absent_keys'][$rel] = $data;
            $this->diagnostics->push(['package' => $pkg, 'locale' => $locale, 'kind' => 'absent_keys', 'lang_dir' => $langDirRel, 'file' => $rel, 'data' => $data]);
        }

        if ($orphan->isNotEmpty()) {
            $data = $orphan->mapWithKeys(fn (string $k) => [$k => $lMap[$k] ?? null])->all();
            $findings['orphan_keys'][$rel] = $data;
            $this->diagnostics->push(['package' => $pkg, 'locale' => $locale, 'kind' => 'orphan_keys', 'lang_dir' => $langDirRel, 'file' => $rel, 'data' => $data]);
        }
    }

    // ─── Placeholder Check ───────────────────────────────────────

    private function checkPlaceholders(string $pkg, string $locale, string $rel, string $lFile, string $langDirRel, array $cTree, array $lTree, array &$findings): void
    {
        $cVals = $this->dotKeysWithValues($cTree);
        $lVals = $this->dotKeysWithValues($lTree);
        $lLines = $this->dotKeysWithLines($lFile);

        foreach ($cVals as $key => $cVal) {
            if (! isset($lVals[$key])) {
                continue;
            }

            $cPlaceholders = $this->extractPlaceholders($cVal);
            $lPlaceholders = $this->extractPlaceholders($lVals[$key]);

            if ($cPlaceholders === $lPlaceholders) {
                continue;
            }

            $missing = array_diff($cPlaceholders, $lPlaceholders);
            $extra = array_diff($lPlaceholders, $cPlaceholders);

            $findings['placeholder_mismatches'][] = [
                'file'    => $rel,
                'key'     => $key,
                'line'    => $lLines[$key] ?? null,
                'missing' => array_values($missing),
                'extra'   => array_values($extra),
            ];

            $this->diagnostics->push([
                'package'  => $pkg, 'locale' => $locale, 'kind' => 'placeholder_mismatch',
                'lang_dir' => $langDirRel, 'file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null,
                'detail'   => 'missing: '.implode(', ', $missing).($extra ? ' | extra: '.implode(', ', $extra) : ''),
            ]);
        }
    }

    private function extractPlaceholders(string $value): array
    {
        if (! preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $value, $m)) {
            return [];
        }

        // Filter out short matches (< 4 chars) that are likely false positives
        // in languages where colons appear mid-word (e.g. Finnish "palvelu:lla").
        // Real Laravel placeholders are descriptive: :attribute, :code, :email, etc.
        $p = array_filter($m[1], fn (string $name) => strlen($name) >= 4);
        $p = array_unique($p);
        sort($p);

        return array_values($p);
    }

    // ─── Empty Value Check ───────────────────────────────────────

    private function checkEmptyValues(string $pkg, string $locale, string $rel, string $lFile, string $langDirRel, array $cTree, array $lTree, array &$findings): void
    {
        $cVals = $this->dotKeysWithValues($cTree);
        $lVals = $this->dotKeysWithValues($lTree);
        $lLines = $this->dotKeysWithLines($lFile);

        foreach ($lVals as $key => $val) {
            if (! isset($cVals[$key]) || trim($cVals[$key]) === '') {
                continue;
            }

            if (trim($val) === '') {
                $findings['empty_values'][] = ['file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null];
                $this->diagnostics->push([
                    'package'  => $pkg, 'locale' => $locale, 'kind' => 'empty_value',
                    'lang_dir' => $langDirRel, 'file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null,
                ]);
            }
        }
    }

    // ─── Untranslated Check ──────────────────────────────────────

    private function checkUntranslated(string $pkg, string $locale, string $rel, string $lFile, string $langDirRel, array $cTree, array $lTree, array &$findings): void
    {
        $cVals = $this->dotKeysWithValues($cTree);
        $lVals = $this->dotKeysWithValues($lTree);
        $lLines = $this->dotKeysWithLines($lFile);

        foreach ($cVals as $key => $cVal) {
            if (! isset($lVals[$key])) {
                continue;
            }

            if ($this->shouldSkipUntranslatedCheck($cVal)) {
                continue;
            }

            if ($cVal === $lVals[$key]) {
                $findings['untranslated'][] = ['file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null];
                $this->diagnostics->push([
                    'package'  => $pkg, 'locale' => $locale, 'kind' => 'untranslated',
                    'lang_dir' => $langDirRel, 'file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null,
                    'detail'   => 'value identical to '.self::CANONICAL_LOCALE.": \"{$cVal}\"",
                ]);
            }
        }
    }

    // ─── Sort Order Check ────────────────────────────────────────

    private function checkSortOrder(string $pkg, string $locale, string $rel, string $cFile, string $lFile, string $langDirRel, array &$findings): void
    {
        $cOrder = array_keys($this->dotKeysWithLines($cFile));
        $lOrder = array_keys($this->dotKeysWithLines($lFile));

        $cSet = array_flip($cOrder);
        $sharedL = array_values(array_filter($lOrder, fn (string $k) => isset($cSet[$k])));
        $sharedC = array_values(array_filter($cOrder, fn (string $k) => in_array($k, $sharedL, true)));

        $violations = 0;

        foreach ($sharedC as $i => $expected) {
            if (! isset($sharedL[$i]) || $sharedL[$i] !== $expected) {
                $findings['sort_violations'][] = ['file' => $rel, 'key' => $expected, 'position' => $i];
                $this->diagnostics->push([
                    'package' => $pkg, 'locale' => $locale, 'kind' => 'sort_violation',
                    'file'    => $rel, 'key' => $expected,
                    'detail'  => "expected at position {$i}, found '".($sharedL[$i] ?? 'EOF')."'",
                ]);

                if (++$violations >= 5) {
                    break;
                }
            }
        }
    }

    // ─── HTML Tag Check ──────────────────────────────────────────

    private function checkHtmlTags(string $pkg, string $locale, string $rel, string $lFile, string $langDirRel, array $cTree, array $lTree, array &$findings): void
    {
        $cVals = $this->dotKeysWithValues($cTree);
        $lVals = $this->dotKeysWithValues($lTree);
        $lLines = $this->dotKeysWithLines($lFile);

        foreach ($cVals as $key => $cVal) {
            if (! isset($lVals[$key])) {
                continue;
            }

            $cTags = $this->extractHtmlTags($cVal);

            if (empty($cTags)) {
                continue;
            }

            $lTags = $this->extractHtmlTags($lVals[$key]);

            if ($cTags !== $lTags) {
                $findings['html_mismatches'][] = [
                    'file'           => $rel,
                    'key'            => $key,
                    'line'           => $lLines[$key] ?? null,
                    'canonical_tags' => $cTags,
                    'locale_tags'    => $lTags,
                ];

                $this->diagnostics->push([
                    'package'  => $pkg, 'locale' => $locale, 'kind' => 'html_mismatch',
                    'lang_dir' => $langDirRel, 'file' => $rel, 'key' => $key, 'line' => $lLines[$key] ?? null,
                    'detail'   => 'expected: '.implode(', ', $cTags).' | found: '.implode(', ', $lTags),
                ]);
            }
        }
    }

    private function extractHtmlTags(string $value): array
    {
        if (! preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9]*)\b[^>]*\/?>/', $value, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $tags = [];

        foreach ($matches as $m) {
            $tag = strtolower($m[1]);
            $full = $m[0];

            if (str_starts_with($full, '</')) {
                $tags[] = "/{$tag}";
            } elseif (str_ends_with($full, '/>')) {
                $tags[] = "{$tag}/";
            } else {
                $tags[] = $tag;
            }
        }

        sort($tags);

        return $tags;
    }

    // ─── Reporting (Pest-style) ─────────────────────────────────

    private function renderAuditReport(bool $verbose): void
    {
        if ($this->auditRows->isEmpty()) {
            $this->warn('No translation files discovered.');

            return;
        }

        $ok = 0;
        $warn = 0;
        $fault = 0;

        $this->auditRows->groupBy('package')->each(function (Collection $rows, string $pkg) use (&$ok, &$warn, &$fault, $verbose) {
            $this->newLine();
            $this->line("  <fg=white;options=bold>{$pkg}</>");

            $rows->each(function (array $r) use ($pkg, &$ok, &$warn, &$fault, $verbose) {
                // Pest-style one-liner per locale
                $badge = match ($r['verdict']) {
                    'ok'    => '  <fg=green> PASS </> ',
                    'warn'  => '  <fg=black;bg=yellow> WARN </> ',
                    default => '  <fg=white;bg=red> FAIL </> ',
                };

                // Use translation_rate for color when available, fallback to coverage
                $displayRate = $r['translation_rate'] ?? $r['coverage'];
                $rateColor = match ($r['verdict']) {
                    'fault' => 'red',
                    'warn'  => 'yellow',
                    default => $displayRate >= 100 ? 'green' : ($displayRate >= 90 ? 'yellow' : 'red'),
                };
                $rateBadge = "<fg={$rateColor}>{$displayRate}%</>";

                match ($r['verdict']) {
                    'ok'    => $ok++,
                    'warn'  => $warn++,
                    default => $fault++,
                };

                if ($r['verdict'] === 'ok') {
                    $this->line("{$badge} {$r['locale']} ({$rateBadge})");

                    return;
                }

                $this->line("{$badge} {$r['locale']} ({$rateBadge}) <fg=gray>→ {$r['summary']}</>");

                // Show error diagnostics inline (always for errors)
                $errorDiags = $this->diagnostics->filter(
                    fn (array $d) => $d['package'] === $pkg
                        && $d['locale'] === $r['locale']
                        && in_array($d['kind'], ['absent_files', 'absent_keys', 'orphan_files', 'orphan_keys', 'placeholder_mismatch', 'html_mismatch', 'parse_failure'])
                );

                $errorDiags->groupBy('kind')->each(fn (Collection $g, string $k) => $this->renderDiagnosticItems($g, $k));

                // Show warning diagnostics only with --details
                if ($verbose) {
                    $warnDiags = $this->diagnostics->filter(
                        fn (array $d) => $d['package'] === $pkg
                            && $d['locale'] === $r['locale']
                            && in_array($d['kind'], ['empty_value', 'untranslated', 'sort_violation'])
                    );

                    $warnDiags->groupBy('kind')->each(fn (Collection $g, string $k) => $this->renderDiagnosticItems($g, $k));
                }
            });
        });

        $this->renderSummary($ok, $warn, $fault, $verbose);
    }

    /**
     * Render diagnostic items Pest-style with full file paths.
     */
    private function renderDiagnosticItems(Collection $group, string $kind): void
    {
        $shown = 0;

        $group->each(function (array $d) use (&$shown, $group) {
            if ($shown >= self::DIAGNOSTIC_CAP) {
                if ($shown === self::DIAGNOSTIC_CAP) {
                    $remaining = $group->count() - self::DIAGNOSTIC_CAP;
                    $this->line("         <fg=gray>… and {$remaining} more</>");
                }

                return false;
            }

            $langDir = $d['lang_dir'] ?? '';
            $locale = $d['locale'] ?? '';

            if (isset($d['files'])) {
                collect($d['files'])->take(self::DIAGNOSTIC_CAP - $shown)->each(function (string $f) use ($langDir, $locale) {
                    $fullPath = "{$langDir}/{$locale}/{$f}";
                    $this->line("         <fg=gray>at</> <fg=cyan>{$fullPath}</>");
                });

                $shown += count($d['files']);

                return;
            }

            if (isset($d['data'])) {
                foreach ($d['data'] as $key => $line) {
                    if ($shown >= self::DIAGNOSTIC_CAP) {
                        break;
                    }

                    $file = $d['file'] ?? '';
                    $fullPath = "{$langDir}/{$locale}/{$file}";
                    $lineRef = $line ? ":{$line}" : '';
                    $this->line("         <fg=gray>at</> <fg=cyan>{$fullPath}{$lineRef}</>  <fg=white>'{$key}'</>");
                    $shown++;
                }

                return;
            }

            $file = $d['file'] ?? '';
            $fullPath = "{$langDir}/{$locale}/{$file}";
            $lineRef = isset($d['line']) ? ":{$d['line']}" : '';
            $key = $d['key'] ?? '';

            $this->line("         <fg=gray>at</> <fg=cyan>{$fullPath}{$lineRef}</>  <fg=white>'{$key}'</>");

            if (isset($d['detail'])) {
                $this->line("         <fg=gray>↳ {$d['detail']}</>");
            }

            $shown++;
        });
    }

    /**
     * Pest-style summary footer.
     */
    private function renderSummary(int $ok, int $warn, int $fault, bool $verbose): void
    {
        $this->newLine();

        $parts = [];

        if ($fault > 0) {
            $parts[] = "<fg=red;options=bold>{$fault} failed</>";
        }

        if ($warn > 0) {
            $parts[] = "<fg=yellow;options=bold>{$warn} warnings</>";
        }

        if ($ok > 0) {
            $parts[] = "<fg=green>{$ok} passed</>";
        }

        $total = $ok + $warn + $fault;
        $this->line('  <fg=white;options=bold>Locales:</>  '.implode('<fg=gray>,</> ', $parts)." <fg=gray>({$total} total)</>");

        if ($this->auditFailed) {
            $this->newLine();

            $kinds = $this->diagnostics->pluck('kind')->unique();

            if ($kinds->intersect(['absent_files', 'absent_keys', 'orphan_files', 'orphan_keys'])->isNotEmpty()) {
                $this->line('  <fg=white;options=bold>Fix:</>     Run <fg=cyan>php artisan unopim:translations:check --fix</>');
            }

            if ($kinds->contains('placeholder_mismatch')) {
                $this->line('  <fg=white;options=bold>Fix:</>     Open file at line shown, fix :placeholder names to match '.self::CANONICAL_LOCALE);
            }

            if ($kinds->contains('html_mismatch')) {
                $this->line('  <fg=white;options=bold>Fix:</>     Open file at line shown, fix HTML tags to match '.self::CANONICAL_LOCALE);
            }
        } elseif ($warn > 0 && ! $verbose) {
            $this->line('  <fg=gray>Use --details to expand warnings</>');
        }

        $this->newLine();
    }

    private function renderCoverageTable(): void
    {
        $this->newLine();
        $this->line('  <fg=white;options=bold>Coverage Report</>');
        $this->line('  <fg=gray>Keys: structural presence │ Translated: actual translation rate</>');
        $this->newLine();

        $this->auditRows->groupBy('package')->each(function (Collection $rows, string $pkg) {
            $this->line("  <fg=white>{$pkg}</>");

            $rows->each(function (array $r) {
                $keyPct = $r['coverage'];
                $keyColor = $keyPct >= 100 ? 'green' : ($keyPct >= 90 ? 'yellow' : 'red');

                $transPct = $r['translation_rate'] ?? $keyPct;
                $transColor = $transPct >= 100 ? 'green' : ($transPct >= 90 ? 'yellow' : 'red');
                $filled = (int) round($transPct / 5);
                $bar = str_repeat('█', $filled).str_repeat('░', 20 - $filled);

                $translated = $r['translated'] ?? $r['present'];
                $untranslated = $r['untranslated'] ?? 0;

                $detail = "<fg=gray>({$r['present']}/{$r['total_keys']} keys</>";

                if ($untranslated > 0) {
                    $detail .= "<fg=gray>, </><fg=yellow>{$untranslated} untranslated</>";
                }

                $detail .= '<fg=gray>)</>';

                $this->line("    {$r['locale']}  <fg={$transColor}>{$bar}</>  <fg={$transColor}>{$transPct}%</>  {$detail}");
            });

            $this->newLine();
        });
    }

    // ─── JSON Output ─────────────────────────────────────────────

    private function outputJson(array $checks): void
    {
        $data = [
            'timestamp'      => now()->toIso8601String(),
            'canonical'      => self::CANONICAL_LOCALE,
            'enabled_checks' => $checks,
            'totals'         => [
                'locales'  => $this->auditRows->count(),
                'passed'   => $this->auditRows->where('verdict', 'ok')->count(),
                'warnings' => $this->auditRows->where('verdict', 'warn')->count(),
                'failed'   => $this->auditRows->where('verdict', 'fault')->count(),
            ],
            'results'     => $this->auditRows->values()->all(),
            'diagnostics' => $this->diagnostics->values()->all(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    // ─── Source-Code Analysis ────────────────────────────────────

    private function analyseSourceCode(?string $onlyPkg, bool $wantMissing, bool $wantUnused, bool $verbose): void
    {
        $this->newLine();
        $this->info('Source-Code Analysis');
        $this->newLine();

        $this->discoverPackages($onlyPkg)->each(function (string $dir) use ($wantMissing, $wantUnused, $verbose) {
            $pkg = basename($dir);
            $langDir = $this->detectLangDir($dir);

            if ($langDir === null) {
                return;
            }

            $canonicalDir = $langDir.'/'.self::CANONICAL_LOCALE;

            if (! File::isDirectory($canonicalDir)) {
                return;
            }

            $ns = $this->inferNamespace($dir, $pkg);
            $declared = $this->harvestDeclaredKeys($canonicalDir, $ns);
            $referenced = $this->harvestReferencedKeys($dir, $ns);

            if ($wantMissing) {
                $undeclared = $referenced->diff($declared)->sort()->values();

                if ($undeclared->isNotEmpty()) {
                    $this->auditFailed = true;
                    $this->line("<fg=cyan>[{$pkg}]</> <fg=red>Referenced but undeclared: {$undeclared->count()} key(s)</>");

                    if ($verbose) {
                        $this->printCappedList($undeclared, 'red');
                    }

                    $this->newLine();
                }
            }

            if ($wantUnused) {
                $unreferenced = $declared->diff($referenced)->sort()->values();

                if ($unreferenced->isNotEmpty()) {
                    $this->line("<fg=cyan>[{$pkg}]</> <fg=yellow>Declared but unreferenced: {$unreferenced->count()} key(s)</>");

                    if ($verbose) {
                        $this->printCappedList($unreferenced, 'yellow');
                    }

                    $this->newLine();
                }
            }
        });
    }

    private function inferNamespace(string $packageDir, string $fallback): string
    {
        foreach (['/src/Providers', '/Providers'] as $sub) {
            $provDir = $packageDir.$sub;

            if (! File::isDirectory($provDir)) {
                continue;
            }

            foreach (File::allFiles($provDir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                if (preg_match('/loadTranslationsFrom\s*\([^,]+,\s*[\'"]([^\'"]+)[\'"]\s*\)/', File::get($file->getPathname()), $m)) {
                    return $m[1];
                }
            }
        }

        return Str::snake($fallback);
    }

    private function harvestDeclaredKeys(string $canonicalDir, string $ns): Collection
    {
        $keys = collect();

        $this->collectPhpFiles($canonicalDir)->each(function (string $file) use ($canonicalDir, $ns, &$keys) {
            try {
                $tree = $this->loadLangArray($file);
                $group = Str::before(Str::after($file, $canonicalDir.'/'), '.php');
                $prefix = "{$ns}::{$group}";

                collect($this->dotKeys($tree))->keys()->each(fn (string $k) => $keys->push("{$prefix}.{$k}"));
            } catch (Throwable) {
                // skip
            }
        });

        return $keys;
    }

    private function harvestReferencedKeys(string $packageDir, string $ns): Collection
    {
        $refs = collect();

        collect(self::SOURCE_DIRS)
            ->map(fn (string $r) => $packageDir.$r)
            ->filter(fn (string $p) => File::isDirectory($p))
            ->each(function (string $scanDir) use ($ns, &$refs) {
                foreach (File::allFiles($scanDir) as $file) {
                    if ($file->getExtension() !== 'php' || Str::contains($file->getPathname(), '/Resources/lang/')) {
                        continue;
                    }

                    $src = File::get($file->getPathname());

                    foreach (self::KEY_PATTERNS as $rx) {
                        if (preg_match_all($rx, $src, $hits)) {
                            foreach ($hits[1] as $key) {
                                if (Str::startsWith($key, $ns.'::')) {
                                    $refs->push($key);
                                }
                            }
                        }
                    }
                }
            });

        return $refs->unique()->values();
    }

    // ─── Fix / Reconcile ─────────────────────────────────────────

    private function reconcileLocales(?string $onlyPkg, ?string $onlyLocale, bool $translate = false, bool $fixUntranslated = false): int
    {
        $this->newLine();
        $this->info('UnoPim — Locale Reconciliation');
        $this->line('   Canonical source: <fg=cyan>'.self::CANONICAL_LOCALE.'</>');

        $fallback = (bool) $this->option('fallback');

        if ($translate) {
            if (! function_exists('magic_ai')) {
                $this->newLine();
                $this->error('MagicAI package is not installed. AI translation is not available.');
                $this->line('  Install the MagicAI package or configure an AI platform in <fg=cyan>Admin > Configuration > Magic AI > Platforms</>.');

                if ($fallback) {
                    $this->warn('--fallback enabled: absent keys will be filled with English values.');
                    $translate = false;
                } else {
                    $this->line('  Use <fg=yellow>--fallback</> to copy English values instead of aborting.');

                    return self::FAILURE;
                }
            } else {
                try {
                    magic_ai()->useDefault();
                    $this->line('   AI translation: <fg=green>enabled</>');

                    if ($fixUntranslated) {
                        $this->line('   Fix untranslated: <fg=green>enabled</> (keys identical to en_US will be re-translated)');
                    }
                } catch (Throwable $e) {
                    $this->newLine();
                    $this->error('No default AI platform configured.');
                    $this->line('  Configure a default AI platform in <fg=cyan>Admin > Configuration > Magic AI > Platforms</> to enable AI translation.');
                    $this->line("  Error: {$e->getMessage()}");

                    if ($fallback) {
                        $this->warn('--fallback enabled: absent keys will be filled with English values.');
                        $translate = false;
                    } else {
                        $this->line('  Use <fg=yellow>--fallback</> to copy English values instead of aborting.');

                        return self::FAILURE;
                    }
                }
            }
        }

        $this->newLine();

        $stats = ['files' => 0, 'injected' => 0, 'pruned' => 0, 'translated' => 0, 'retranslated' => 0];

        foreach ($this->discoverPackages($onlyPkg) as $dir) {
            $langDir = $this->detectLangDir($dir);

            if ($langDir === null) {
                continue;
            }

            $canonicalDir = $langDir.'/'.self::CANONICAL_LOCALE;

            if (! File::isDirectory($canonicalDir)) {
                continue;
            }

            $canonicalFiles = $this->collectPhpFiles($canonicalDir);

            if ($canonicalFiles->isEmpty()) {
                continue;
            }

            $locales = $this->listNonCanonicalLocales($langDir, $onlyLocale);

            if ($locales->isEmpty()) {
                continue;
            }

            $this->info('Package: '.basename($dir));

            $canonicalFiles->each(function (string $cFile) use ($canonicalDir, $langDir, $locales, $translate, $fixUntranslated, $fallback, &$stats) {
                $rel = Str::after($cFile, $canonicalDir.'/');

                try {
                    $cTree = $this->loadLangArray($cFile);
                } catch (Throwable) {
                    $this->line("  <fg=red>Cannot parse {$rel} — skipped.</>");

                    return;
                }

                $cFlat = $this->dotKeys($cTree);

                $locales->each(function (string $loc) use ($langDir, $rel, $cTree, $cFlat, $translate, $fixUntranslated, $fallback, &$stats) {
                    $target = "{$langDir}/{$loc}/{$rel}";
                    $lTree = [];

                    if (File::exists($target)) {
                        try {
                            $lTree = $this->loadLangArray($target);
                        } catch (Throwable) {
                            $lTree = [];
                        }
                    } else {
                        File::ensureDirectoryExists(dirname($target));
                    }

                    $lFlat = $this->dotKeys($lTree);
                    $absentKeys = array_diff_key($cFlat, $lFlat);
                    $absent = count($absentKeys);
                    $orphan = count(array_diff_key($lFlat, $cFlat));

                    // Detect untranslated keys (locale value identical to en_US)
                    $untranslatedKeys = [];

                    if ($fixUntranslated) {
                        $cFlatValues = $this->dotKeysWithValues($cTree);
                        $lFlatValues = $this->dotKeysWithValues($lTree);
                        $commonKeys = array_intersect_key($cFlatValues, $lFlatValues);

                        foreach ($commonKeys as $dotKey => $cValue) {
                            if (
                                is_string($cValue)
                                && is_string($lFlatValues[$dotKey])
                                && $cValue === $lFlatValues[$dotKey]
                                && ! $this->shouldSkipUntranslatedCheck($cValue)
                            ) {
                                $untranslatedKeys[$dotKey] = $cValue;
                            }
                        }
                    }

                    $untranslatedCount = count($untranslatedKeys);

                    if ($absent === 0 && $orphan === 0 && $untranslatedCount === 0) {
                        return;
                    }

                    $translations = [];

                    // Merge absent + untranslated keys into a single AI batch
                    if ($translate && ($absent > 0 || $untranslatedCount > 0)) {
                        $keysToTranslate = array_merge($absentKeys, $untranslatedKeys);
                        $translations = $this->translateBatch($keysToTranslate, $loc, $fallback);

                        if ($translations === false) {
                            return;
                        }

                        $stats['translated'] += count($translations);
                    }

                    $merged = $this->overlayOnCanonical($cTree, $lTree, $translations);
                    $this->persistLangArray($target, $merged);

                    $stats['files']++;
                    $stats['injected'] += $absent;
                    $stats['pruned'] += $orphan;

                    $translatedCount = count($translations);
                    $parts = [];

                    if ($absent > 0 || $orphan > 0) {
                        $parts[] = "+{$absent} added, -{$orphan} removed";
                    }

                    if ($untranslatedCount > 0) {
                        $stats['retranslated'] += $untranslatedCount;
                        $parts[] = "<fg=blue>{$untranslatedCount} untranslated re-translated</>";
                    }

                    if ($translate && $translatedCount > 0) {
                        $copiedCount = $absent - min($translatedCount, $absent);
                        $parts[] = "<fg=green>{$translatedCount} AI-translated</>";

                        if ($copiedCount > 0) {
                            $parts[] = "<fg=gray>{$copiedCount} copied</>";
                        }
                    }

                    $this->line("  <fg=yellow>{$loc}/{$rel}:</> ".implode(', ', $parts));
                });
            });

            $this->newLine();
        }

        $this->line(str_repeat('─', 64));
        $this->line("Reconciled: {$stats['files']} file(s) — +{$stats['injected']} key(s) added, -{$stats['pruned']} key(s) removed");

        if ($translate && $stats['translated'] > 0) {
            $this->line("AI translated: {$stats['translated']} key(s)");
        }

        $retranslated = $stats['retranslated'];

        if ($retranslated > 0) {
            $this->line("Untranslated keys re-translated: {$retranslated} key(s)");
        }

        $this->line(str_repeat('─', 64));
        $this->newLine();

        if ($stats['files'] > 0 || $retranslated > 0) {
            $method = $translate && $stats['translated'] > 0
                ? 'AI-translated (with '.self::CANONICAL_LOCALE.' fallback)'
                : self::CANONICAL_LOCALE.' values';
            $this->info("Absent keys filled with {$method}.");

            if ($retranslated > 0) {
                $this->info('Untranslated values (identical to en_US) were re-translated via AI.');
            }

            $this->line('<fg=yellow>Re-run without --fix to confirm the audit passes.</>');
        } else {
            $this->info('Every locale is already reconciled — nothing to do.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Translate a batch of absent keys using AI.
     *
     * @param  array<string, string>  $absentKeys  Dot-notated key => English value pairs
     * @param  string  $locale  Target locale code (e.g. 'fr_FR')
     * @param  bool  $fallback  When true, copy English on failure; when false, skip the file entirely
     * @return array<string, string>|false Translated pairs, empty array (fallback), or false to skip
     */
    private function translateBatch(array $absentKeys, string $locale, bool $fallback = false): array|false
    {
        $languageName = self::LOCALE_NAMES[$locale] ?? $locale;
        $total = count($absentKeys);

        $this->line("    <fg=cyan>Translating {$languageName} ({$locale}): {$total} key(s)...</>");

        // Chunk large batches to avoid token limits (max 100 keys per API call)
        $chunkSize = 100;
        $allTranslated = [];

        $chunks = array_chunk($absentKeys, $chunkSize, true);
        $chunkCount = count($chunks);

        foreach ($chunks as $i => $chunk) {
            if ($chunkCount > 1) {
                $chunkNum = $i + 1;
                $this->line("      <fg=gray>Chunk {$chunkNum}/{$chunkCount} (".count($chunk).' keys)...</>');
            }

            $result = $this->translateChunk($chunk, $locale, $languageName, $fallback);

            if ($result === false) {
                return false;
            }

            $allTranslated = array_merge($allTranslated, $result);
        }

        return $allTranslated;
    }

    /**
     * Translate a single chunk of keys via AI.
     *
     * @return array<string, string>|false
     */
    private function translateChunk(array $keys, string $locale, string $languageName, bool $fallback): array|false
    {
        $payload = json_encode($keys, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $systemPrompt = implode("\n", [
            "You are a professional translator. Translate Laravel translation values from English to {$languageName} ({$locale}).",
            'Rules:',
            '- Preserve all :placeholder tokens (like :name, :count, :attribute) exactly as-is — do NOT translate them.',
            '- Preserve all surrounding punctuation and formatting: dashes (--), ellipsis (...), brackets, pipes, and wrapper characters.',
            '  Example: "-- Select Model --" must become "-- Modèle sélectionné --" (keep the -- wrapper).',
            '  Example: "Loading models..." must keep the trailing "..." in the translation.',
            '- Translate the COMPLETE value — do NOT truncate, shorten, or omit any part of the sentence.',
            '- Translate naturally and idiomatically, not word-for-word.',
            '- Return ONLY a valid JSON object with the exact same keys and translated values.',
            '- Do not add any explanation, markdown formatting, or code fences.',
            '- For English variant locales (en_AU, en_GB, en_NZ), use region-appropriate spelling (e.g. "colour" for en_GB).',
        ]);

        try {
            $response = magic_ai()
                ->useDefault()
                ->setSystemPrompt($systemPrompt)
                ->setPrompt($payload, 'text')
                ->setTemperature(0.3)
                ->setMaxTokens(16384)
                ->ask();

            $response = $this->extractJsonFromResponse($response);
            $translated = json_decode($response, true);

            if (! is_array($translated)) {
                $this->line('    <fg=red>AI returned invalid JSON.</>');

                if ($fallback) {
                    $this->line('    <fg=yellow>--fallback: copying English values.</>');

                    return [];
                }

                $this->line('    <fg=red>Skipping file. Use --fallback to copy English values instead.</>');

                return false;
            }

            return array_intersect_key($translated, $keys);
        } catch (Throwable $e) {
            $this->line("    <fg=red>AI translation failed: {$e->getMessage()}</>");

            if ($fallback) {
                $this->line('    <fg=yellow>--fallback: copying English values.</>');

                return [];
            }

            $this->line('    <fg=red>Skipping file. Use --fallback to copy English values instead.</>');

            return false;
        }
    }

    /**
     * Extract JSON from AI response that may contain markdown code fences.
     */
    /**
     * Check if a value should be skipped from untranslated detection.
     *
     * Single words, acronyms, and technical tokens are often legitimately
     * identical across languages and should not be flagged as untranslated.
     */
    private function shouldSkipUntranslatedCheck(string $value): bool
    {
        if (mb_strlen($value) < self::UNTRANSLATED_MIN_LENGTH || trim($value) === '') {
            return true;
        }

        foreach (self::UNTRANSLATED_SKIP_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function extractJsonFromResponse(string $response): string
    {
        $response = trim($response);

        // Strip markdown code fences if present
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $response, $matches)) {
            return trim($matches[1]);
        }

        return $response;
    }

    /**
     * Overlay locale values on canonical structure, with optional AI translations for absent keys.
     *
     * @param  array<string, string>  $translations  Dot-notated key => translated value pairs
     * @param  string  $prefix  Current dot-notation prefix for recursive tracking
     */
    private function overlayOnCanonical(array $canonical, array $locale, array $translations = [], string $prefix = ''): array
    {
        $merged = [];

        foreach ($canonical as $key => $cVal) {
            $dotKey = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($cVal)) {
                $lVal = isset($locale[$key]) && is_array($locale[$key]) ? $locale[$key] : [];
                $merged[$key] = $this->overlayOnCanonical($cVal, $lVal, $translations, $dotKey);
            } else {
                if (isset($translations[$dotKey])) {
                    // AI translation takes priority (covers both absent and untranslated keys)
                    $merged[$key] = $translations[$dotKey];
                } elseif (array_key_exists($key, $locale) && ! is_array($locale[$key])) {
                    $merged[$key] = $locale[$key];
                } else {
                    $merged[$key] = $cVal;
                }
            }
        }

        return $merged;
    }

    // ─── Discovery Helpers ───────────────────────────────────────

    private function discoverPackages(?string $only = null): Collection
    {
        return collect(self::PACKAGE_ROOTS)
            ->map(fn (string $r) => base_path($r))
            ->filter(fn (string $p) => File::isDirectory($p))
            ->flatMap(fn (string $p) => File::directories($p))
            ->when($only, fn (Collection $c) => $c->filter(
                fn (string $p) => Str::lower(basename($p)) === Str::lower($only)
            ))
            ->sort()
            ->values();
    }

    private function detectLangDir(string $packageDir): ?string
    {
        foreach (self::LANG_CANDIDATES as $candidate) {
            $full = $packageDir.$candidate;

            if (File::isDirectory($full)) {
                return $full;
            }
        }

        return null;
    }

    private function listNonCanonicalLocales(string $langDir, ?string $onlyLocale): Collection
    {
        return collect(File::directories($langDir))
            ->map(fn (string $d) => basename($d))
            ->reject(fn (string $d) => $d === self::CANONICAL_LOCALE)
            ->when($onlyLocale, fn (Collection $c) => $c->filter(
                fn (string $l) => Str::lower($l) === Str::lower($onlyLocale)
            ))
            ->sort()
            ->values();
    }

    // ─── File & Array Utilities ──────────────────────────────────

    private function collectPhpFiles(string $dir): Collection
    {
        if (! File::isDirectory($dir)) {
            return collect();
        }

        return collect(File::allFiles($dir))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->map(fn ($f) => $f->getPathname())
            ->sort()
            ->values();
    }

    private function loadLangArray(string $path): array
    {
        ob_start();

        try {
            $data = include $path;
        } catch (Throwable $e) {
            ob_end_clean();

            throw new RuntimeException("Cannot include {$path}: ".$e->getMessage());
        }

        ob_end_clean();

        if (! is_array($data)) {
            throw new RuntimeException("File does not yield an array: {$path}");
        }

        return $data;
    }

    private function dotKeys(array $tree, string $prefix = ''): array
    {
        return collect($tree)->flatMap(function ($value, $key) use ($prefix) {
            $dot = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            return is_array($value) ? $this->dotKeys($value, $dot) : [$dot => true];
        })->all();
    }

    private function dotKeysWithValues(array $tree, string $prefix = ''): array
    {
        return collect($tree)->flatMap(function ($value, $key) use ($prefix) {
            $dot = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            return is_array($value) ? $this->dotKeysWithValues($value, $dot) : [$dot => (string) $value];
        })->all();
    }

    private function dotKeysWithLines(string $file): array
    {
        $lines = explode("\n", File::get($file));
        $map = [];
        $stack = [];

        foreach ($lines as $idx => $line) {
            if (! preg_match('/^(\s*)[\'"]([^\'"]+)[\'"]\s*=>/', $line, $m)) {
                continue;
            }

            $depth = (int) (Str::length($m[1]) / 4);
            $key = $m[2];

            while (count($stack) > $depth) {
                array_pop($stack);
            }

            $stack[$depth] = $key;

            if (! preg_match('/=>\s*\[/', $line)) {
                $map[implode('.', array_slice($stack, 0, $depth + 1))] = $idx + 1;
            }
        }

        return $map;
    }

    private function persistLangArray(string $path, array $data): void
    {
        File::put($path, "<?php\n\nreturn ".$this->renderPhpArray($data, 1).";\n");
    }

    private function renderPhpArray(array $items, int $depth): string
    {
        if (empty($items)) {
            return '[]';
        }

        $pad = str_repeat('    ', $depth);
        $closePad = str_repeat('    ', $depth - 1);
        $entries = [];

        foreach ($items as $k => $v) {
            $safeKey = str_replace("'", "\\'", (string) $k);

            if (is_array($v)) {
                $entries[] = "{$pad}'{$safeKey}' => ".$this->renderPhpArray($v, $depth + 1);
            } else {
                $safeVal = str_replace("'", "\\'", (string) $v);
                $entries[] = "{$pad}'{$safeKey}' => '{$safeVal}'";
            }
        }

        return "[\n".implode(",\n", $entries).",\n{$closePad}]";
    }

    private function condenseSummary(array $findings): string
    {
        $parts = collect();

        $countMap = [
            'absent_files'           => 'absent file',
            'orphan_files'           => 'orphan file',
            'absent_keys'            => null, // nested
            'orphan_keys'            => null, // nested
            'parse_failures'         => 'parse failure',
            'placeholder_mismatches' => 'placeholder mismatch',
            'empty_values'           => 'empty value',
            'untranslated'           => 'untranslated key',
            'sort_violations'        => 'sort violation',
            'html_mismatches'        => 'HTML tag mismatch',
        ];

        foreach ($countMap as $key => $label) {
            if (empty($findings[$key])) {
                continue;
            }

            if ($label === null) {
                // Nested key arrays: sum up counts
                $n = collect($findings[$key])->map(fn ($v) => count($v))->sum();
                $label = str_replace('_', ' ', $key);
            } else {
                $n = count($findings[$key]);
            }

            $parts->push("{$n} {$label}(s)");
        }

        return $parts->implode(', ') ?: '—';
    }

    private function printCappedList(Collection $items, string $color): void
    {
        $items->take(self::DIAGNOSTIC_CAP)->each(fn (string $k) => $this->line("  <fg={$color}>-</> {$k}"));

        if ($items->count() > self::DIAGNOSTIC_CAP) {
            $this->line('  <fg=gray>... and '.($items->count() - self::DIAGNOSTIC_CAP).' more</>');
        }
    }
}
