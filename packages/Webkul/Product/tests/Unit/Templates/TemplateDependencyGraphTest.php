<?php

use Webkul\Product\Templates\TemplateDependencyGraph;

it('finds no cycle in an acyclic graph', function () {
    $graph = new TemplateDependencyGraph([
        'description'       => ['name', 'short_description'],
        'short_description' => ['name'],
        'meta_description'  => ['description', 'short_description'],
    ]);

    expect($graph->cycles())->toBe([]);
});

it('detects a self reference', function () {
    expect((new TemplateDependencyGraph(['description' => ['description']]))->cycles())
        ->toBe([['description', 'description']]);
});

it('detects a two step cycle once', function () {
    $graph = new TemplateDependencyGraph([
        'description'       => ['short_description'],
        'short_description' => ['description'],
    ]);

    expect($graph->cycles())->toBe([['description', 'short_description', 'description']]);
});

it('detects a longer cycle behind an acyclic entry point', function () {
    $graph = new TemplateDependencyGraph([
        'entry' => ['a'],
        'a'     => ['b'],
        'b'     => ['c'],
        'c'     => ['a', 'name'],
    ]);

    expect($graph->cycles())->toBe([['a', 'b', 'c', 'a']]);
});

it('ignores references to attributes that hold no template', function () {
    expect((new TemplateDependencyGraph(['description' => ['name', 'price']]))->cycles())->toBe([]);
});

it('returns the same result when asked twice', function () {
    $graph = new TemplateDependencyGraph(['a' => ['b'], 'b' => ['a']]);

    expect($graph->cycles())->toBe($graph->cycles());
});
