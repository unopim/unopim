<?php

use Illuminate\Support\Facades\Queue;
use Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob;
use Webkul\MagicAI\Jobs\SaveTranslatedDataJob;

use function Pest\Laravel\postJson;

it('denies every translation endpoint without the ai-agent permission', function (string $route) {
    $this->loginWithPermissions('custom', ['dashboard']);

    postJson(route($route))->assertForbidden();
})->with([
    'admin.magic_ai.check.is_translatable',
    'admin.magic_ai.translate',
    'admin.magic_ai.store.translated',
    'admin.magic_ai.check.is_all_attribute_translatable',
    'admin.magic_ai.translate.all.attribute',
    'admin.magic_ai.store.translated.all_attribute',
]);

it('queues the single field translation instead of writing it in the request', function () {
    $this->loginWithPermissions('all');

    Queue::fake();

    postJson(route('admin.magic_ai.store.translated'), [
        'resource_id'    => 1,
        'field'          => 'description',
        'targetChannel'  => 'default',
        'translatedData' => json_encode([['locale' => 'fr_FR', 'content' => '<p>Bonjour</p>']]),
    ])->assertOk();

    Queue::assertPushed(SaveTranslatedDataJob::class);
});

it('queues the all-attribute translation instead of writing it in the request', function () {
    $this->loginWithPermissions('all');

    Queue::fake();

    postJson(route('admin.magic_ai.store.translated.all_attribute'), [
        'resource_id'    => 1,
        'targetChannel'  => 'default',
        'translatedData' => json_encode(['fr_FR' => ['description' => ['field' => 'description', 'content' => '<p>Bonjour</p>']]]),
    ])->assertOk();

    Queue::assertPushed(SaveTranslatedAllAttributesJob::class);
});
