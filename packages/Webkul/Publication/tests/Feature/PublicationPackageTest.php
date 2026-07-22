<?php

it('merges the publication config', function (): void {
    expect(config('publication.queue'))->toBe('publication')
        ->and(config('publication.types'))->toBeArray();
});
