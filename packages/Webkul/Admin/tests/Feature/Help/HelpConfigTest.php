<?php

it('help config exposes sections and a cta', function () {
    $sections = config('help.sections');
    $cta = config('help.cta');

    expect($sections)->toBeArray()->not->toBeEmpty();
    expect($cta)->toBeArray()->toHaveKeys(['icon', 'title', 'sub', 'url', 'label']);

    $allItems = collect($sections)->flatMap(fn ($s) => $s['items']);

    expect($allItems)->toHaveCount(6);

    $allItems->each(function ($item) {
        expect($item)->toHaveKeys(['icon', 'title', 'description', 'url']);
    });

    expect($allItems->pluck('url'))->toContain('https://unopim.com/cloud-hosting/');
});
