<?php

use Webkul\Publication\Models\Publication;

it('merges the publication config', function (): void {
    expect(config('publication.queue'))->toBe(env('PUBLICATION_QUEUE', 'publication'))
        ->and(config('publication.types'))->toBeArray();
});

it('generates an opaque uuid v4, not a timestamp-disclosing v7', function (): void {
    $publication = Publication::factory()->create();

    // The version nibble ("4") sits at a fixed offset in every UUID; v7's
    // leading 48 bits would instead encode the creation timestamp, which is
    // exactly what makes a v4 identifier "opaque" as the design claims.
    expect($publication->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});
