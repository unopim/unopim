<?php

use Webkul\AdminApi\Models\Apikey;

/*
 * A revoked integration key must fail closed at the ACL middleware even if its
 * OAuth token is still technically valid — revocation must not depend solely on
 * the OAuth client being deleted.
 */
it('denies a revoked api key at the acl middleware', function () {
    $headers = $this->getAuthenticationHeaders();

    Apikey::query()->latest('id')->first()->update(['revoked' => true]);

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.index'))->assertStatus(403);
});
