<?php

declare(strict_types=1);

/**
 * Merge per-chunk Pest TIA graphs (schema 1) into a single baseline graph.
 *
 * Edges reference integer ids into each graph's own `files` table, so ids are
 * remapped onto a shared table before the edge lists are unioned. Chunks are
 * recorded from the same commit, so the last fingerprint and metadata win.
 *
 * Usage: php merge-tia-graphs.php <output-graph.json> <chunk-graph.json>...
 */
$output = $argv[1] ?? null;
$inputs = array_slice($argv, 2);

if ($output === null || $inputs === []) {
    fwrite(STDERR, "Usage: merge-tia-graphs.php <output> <input>...\n");
    exit(1);
}

$files = [];
$fileIds = [];
$merged = null;

foreach ($inputs as $path) {
    $graph = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    if (($graph['schema'] ?? null) !== 1) {
        fwrite(STDERR, "Unsupported graph schema in {$path}\n");
        exit(1);
    }

    $map = [];

    foreach ($graph['files'] as $id => $file) {
        if (! isset($fileIds[$file])) {
            $fileIds[$file] = count($files);
            $files[] = $file;
        }

        $map[$id] = $fileIds[$file];
    }

    if ($merged === null) {
        $merged = $graph;
        $merged['files'] = [];
        $merged['edges'] = [];
        $merged['baselines'] = [];
        $merged['test_tables'] = [];
        $merged['test_inertia_components'] = [];
    }

    foreach ($graph['edges'] as $test => $ids) {
        $remapped = array_map(static fn ($id) => $map[$id], $ids);
        $merged['edges'][$test] = array_values(array_unique(array_merge($merged['edges'][$test] ?? [], $remapped)));
    }

    $merged['test_tables'] = ($graph['test_tables'] ?? []) + $merged['test_tables'];
    $merged['test_inertia_components'] = ($graph['test_inertia_components'] ?? []) + $merged['test_inertia_components'];
    $merged['js_file_to_components'] = $graph['js_file_to_components'] ?? [];
    $merged['fingerprint'] = $graph['fingerprint'] ?? [];

    foreach ($graph['baselines'] ?? [] as $branch => $baseline) {
        $results = ($baseline['results'] ?? []) + ($merged['baselines'][$branch]['results'] ?? []);
        $merged['baselines'][$branch] = array_merge($merged['baselines'][$branch] ?? [], $baseline);
        $merged['baselines'][$branch]['results'] = $results;
    }
}

$merged['files'] = $files;

if (! is_dir(dirname($output))) {
    mkdir(dirname($output), 0777, true);
}

file_put_contents($output, json_encode($merged, JSON_UNESCAPED_SLASHES));

echo sprintf("Merged %d graphs: %d tests, %d files\n", count($inputs), count($merged['edges']), count($files));
