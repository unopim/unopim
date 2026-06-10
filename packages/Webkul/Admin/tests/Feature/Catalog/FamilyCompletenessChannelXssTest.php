<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;

it('escapes a malicious channel name on the family completeness page', function () {
    $this->loginAsAdmin();

    $channel = Channel::first();

    $payload = "'><img src=x onerror=alert(document.domain)>";

    foreach ($channel->translations as $translation) {
        $translation->name = $payload;
        $translation->save();
    }

    $family = AttributeFamily::first();

    $response = $this->get(route('admin.catalog.families.edit', ['id' => $family->id, 'completeness' => 1]));

    $response->assertOk();

    $response->assertDontSee("'><img src=x onerror", false);
    $response->assertDontSee('onerror=alert(document.domain)>', false);

    $response->assertSee('&#039;&gt;&lt;img', false);
});
