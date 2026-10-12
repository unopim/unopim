<?php

/*
 * An explicit locale segment must resolve strictly: a passport with no version
 * in the requested locale returns 404, rather than serving another locale's
 * content under a cacheable 200.
 */
it('returns 404 for an explicit locale that has no published version', function (): void {
    $version = $this->publishedPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/zz_ZZ')
        ->assertStatus(404);
});

it('still renders the canonical published locale', function (): void {
    $version = $this->publishedPassportFixture();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)
        ->assertOk();
});
