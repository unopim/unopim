<?php

/*
 * Regression guard for Vue compiler error #57 (X_V_TEXT_WITH_CHILDREN).
 *
 * The <x-admin::form.control-group.label> component ALWAYS renders child nodes
 * (an unsaved-badge <span>). Putting `v-text` directly on that component makes the
 * compiled runtime template `<label v-text=".."><span>..</span></label>`, which Vue
 * rejects with compiler-57 — killing the whole template compile and hanging the
 * Import "create" page. The correct pattern (used by filter-fields.blade) is to put
 * v-text on an empty child <span>, never on the label component itself.
 */

$viewsDir = dirname(__DIR__, 3).'/src/Resources/views';

function labelTagsWithVText(string $file): int
{
    $contents = file_get_contents($file);

    // Match each <x-admin::form.control-group.label ...> opening tag and check
    // whether that opening tag itself carries a v-text binding.
    if (! preg_match_all('/<x-admin::form\.control-group\.label\b[^>]*>/s', $contents, $matches)) {
        return 0;
    }

    return count(array_filter($matches[0], fn ($tag) => str_contains($tag, 'v-text')));
}

it('does not put v-text on the label component in import-setting-fields (compiler-57 guard)', function () use ($viewsDir) {
    $file = $viewsDir.'/components/data-transfer/import-setting-fields.blade.php';

    expect(file_exists($file))->toBeTrue();
    expect(labelTagsWithVText($file))->toBe(0);
});

it('has no v-text directly on any control-group.label across admin blades', function () use ($viewsDir) {
    $offenders = [];

    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS));

    foreach ($it as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if (labelTagsWithVText($file->getPathname()) > 0) {
            $offenders[] = str_replace($viewsDir.'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});
