<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use Webkul\MagicAI\Console\Support\StdinReader;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ModelDiscovery;

use function Pest\Laravel\artisan;

const WIZARD = 'unopim:magic-ai:platform:add';

function wizardLabel(string $key): string
{
    return trans('admin::app.configuration.platform.'.$key);
}

function fakeDiscovery(array|Throwable $result): void
{
    test()->mock(ModelDiscovery::class, function ($mock) use ($result) {
        $expectation = $mock->shouldReceive('discover');

        $result instanceof Throwable
            ? $expectation->andThrow($result)
            : $expectation->andReturn(['models' => $result, 'api_url' => 'https://api.openai.com/v1']);
    });
}

function answerConnection(PendingCommand $command, string $label, string $apiKey): PendingCommand
{
    return $command
        ->expectsQuestion(wizardLabel('fields.provider'), AiProvider::OpenAI->value)
        ->expectsQuestion(wizardLabel('fields.label'), $label)
        ->expectsQuestion(wizardLabel('fields.api-url'), 'https://api.openai.com/v1')
        ->expectsQuestion(wizardLabel('fields.api-key'), $apiKey);
}

function answerFlags(PendingCommand $command, bool $default = true, bool $managed = true): PendingCommand
{
    return $command
        ->expectsConfirmation(wizardLabel('fields.is-default'), $default ? 'yes' : 'no')
        ->expectsConfirmation(wizardLabel('fields.status'), 'yes')
        ->expectsConfirmation(wizardLabel('command.managed'), $managed ? 'yes' : 'no')
        ->expectsConfirmation(wizardLabel('command.confirm-save'), 'yes');
}

it('creates a managed default platform from the fetched models', function () {
    fakeDiscovery(['gpt-4o', 'gpt-4o-mini', 'dall-e-3']);

    $previousDefault = MagicAIPlatform::factory()->default()->create();

    $command = answerConnection(artisan(WIZARD), 'Hosted AI', 'sk-hosted-key')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o', 'gpt-4o-mini'])
        ->expectsQuestion(wizardLabel('command.models-additional'), '');

    answerFlags($command)
        ->expectsOutputToContain(wizardLabel('command.field'))
        ->assertSuccessful()->run();

    $platform = MagicAIPlatform::where('label', 'Hosted AI')->sole();

    $storedKey = DB::table('magic_ai_platforms')->where('id', $platform->id)->value('api_key');

    expect($platform->is_managed)->toBeTrue()
        ->and($platform->is_default)->toBeTrue()
        ->and($platform->status)->toBeTrue()
        ->and($platform->provider)->toBe(AiProvider::OpenAI->value)
        ->and($platform->api_url)->toBe('https://api.openai.com/v1')
        ->and($platform->models)->toBe('gpt-4o,gpt-4o-mini')
        ->and($platform->extras)->toBeNull()
        ->and($storedKey)->not->toContain('sk-hosted-key')
        ->and(Crypt::decryptString($storedKey))->toBe('sk-hosted-key')
        ->and($previousDefault->fresh()->is_default)->toBeFalse();
});

it('falls back to typed models when the fetch fails', function () {
    fakeDiscovery(new RuntimeException('api.openai.com is unreachable'));

    $command = answerConnection(artisan(WIZARD), 'Typed models', 'sk-typed-key')
        ->expectsOutputToContain('api.openai.com is unreachable')
        ->expectsQuestion(wizardLabel('command.models-manual'), 'gpt-5.1, ~my-model');

    answerFlags($command, default: false)->assertSuccessful()->run();

    expect(MagicAIPlatform::where('label', 'Typed models')->sole()->models)->toBe('gpt-5.1,my-model');
});

it('rejects a default platform that is disabled', function () {
    fakeDiscovery(['gpt-4o']);

    answerConnection(artisan(WIZARD), 'Disabled default', 'sk-some-key')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o'])
        ->expectsQuestion(wizardLabel('command.models-additional'), '')
        ->expectsConfirmation(wizardLabel('fields.is-default'), 'yes')
        ->expectsConfirmation(wizardLabel('fields.status'), 'no')
        ->expectsOutputToContain(trans('admin::app.configuration.platform.message.default-requires-enabled'))
        ->assertFailed();

    expect(MagicAIPlatform::where('label', 'Disabled default')->exists())->toBeFalse();
});

it('creates an unmanaged platform', function () {
    fakeDiscovery(['gpt-4o']);

    $command = answerConnection(artisan(WIZARD), 'Client AI', 'sk-client-key')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o'])
        ->expectsQuestion(wizardLabel('command.models-additional'), 'my-finetune')
        ->expectsOutputToContain(trans('admin::app.configuration.platform.command.models-unlisted', ['models' => 'my-finetune']));

    answerFlags($command, default: false, managed: false)->assertSuccessful()->run();

    $platform = MagicAIPlatform::where('label', 'Client AI')->sole();

    expect($platform->is_managed)->toBeFalse()
        ->and($platform->models)->toBe('gpt-4o,my-finetune');
});

it('requires an API key when adding a platform', function () {
    $count = MagicAIPlatform::count();

    answerConnection(artisan(WIZARD), 'No key', '')->assertFailed();

    expect(MagicAIPlatform::count())->toBe($count);
});

it('saves nothing when the summary is declined', function () {
    fakeDiscovery(['gpt-4o']);

    answerConnection(artisan(WIZARD), 'Declined', 'sk-some-key')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o'])
        ->expectsQuestion(wizardLabel('command.models-additional'), '')
        ->expectsConfirmation(wizardLabel('fields.is-default'), 'no')
        ->expectsConfirmation(wizardLabel('fields.status'), 'yes')
        ->expectsConfirmation(wizardLabel('command.managed'), 'yes')
        ->expectsConfirmation(wizardLabel('command.confirm-save'), 'no')
        ->assertSuccessful();

    expect(MagicAIPlatform::where('label', 'Declined')->exists())->toBeFalse();
});

it('creates a platform from options with the key read from standard input', function () {
    $this->mock(StdinReader::class)->shouldReceive('readLine')->once()->andReturn('sk-ci-key');

    artisan(WIZARD, [
        '--no-interaction' => true,
        '--provider'       => AiProvider::OpenAI->value,
        '--label'          => 'CI platform',
        '--api-url'        => 'https://api.openai.com/v1',
        '--models'         => 'gpt-4o,gpt-4o-mini',
        '--key-stdin'      => true,
    ])->assertSuccessful();

    $platform = MagicAIPlatform::where('label', 'CI platform')->sole();

    expect($platform->safeApiKey())->toBe('sk-ci-key')
        ->and($platform->is_managed)->toBeTrue()
        ->and($platform->models)->toBe('gpt-4o,gpt-4o-mini');
});

it('fails without interaction when a required value is missing', function (array $options, string $message) {
    $count = MagicAIPlatform::count();

    artisan(WIZARD, ['--no-interaction' => true] + $options)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(MagicAIPlatform::count())->toBe($count);
})->with([
    'provider' => fn () => [[], trans('admin::app.configuration.platform.command.missing-option', ['option' => 'provider'])],
    'key'      => fn () => [['--provider' => 'openai', '--models' => 'gpt-4o'], trans('admin::app.configuration.platform.command.key-stdin-required')],
]);

it('fails without interaction when the models are missing or invalid', function (?string $models, string $message) {
    $this->mock(StdinReader::class)->shouldReceive('readLine')->andReturn('sk-ci-key');

    artisan(WIZARD, array_filter([
        '--no-interaction' => true,
        '--provider'       => AiProvider::OpenAI->value,
        '--models'         => $models,
        '--key-stdin'      => true,
    ]))->expectsOutputToContain($message)->assertFailed();
})->with([
    'missing' => fn () => [null, trans('admin::app.configuration.platform.command.missing-option', ['option' => 'models'])],
    'invalid' => fn () => ['gpt 4o', trans('admin::app.configuration.platform.message.invalid-model-names', ['names' => 'gpt 4o'])],
]);

it('refuses provider settings on a managed platform', function () {
    $this->mock(StdinReader::class)->shouldReceive('readLine')->andReturn('sk-azure-key');

    artisan(WIZARD, [
        '--no-interaction' => true,
        '--provider'       => AiProvider::Azure->value,
        '--api-url'        => 'https://api.openai.com/v1',
        '--models'         => 'gpt-4o',
        '--key-stdin'      => true,
    ])->expectsOutputToContain(trans('admin::app.configuration.platform.command.managed-extras'))->assertFailed();

    artisan(WIZARD, [
        '--no-interaction' => true,
        '--provider'       => AiProvider::Azure->value,
        '--label'          => 'Azure client',
        '--api-url'        => 'https://api.openai.com/v1',
        '--models'         => 'gpt-4o',
        '--key-stdin'      => true,
        '--unmanaged'      => true,
    ])->assertSuccessful();

    expect(MagicAIPlatform::where('label', 'Azure client')->sole()->extras)
        ->toBe(['deployment' => 'gpt-4o', 'api_version' => '2024-10-21']);
});

it('rejects an unsafe endpoint without interaction', function () {
    $this->mock(StdinReader::class)->shouldReceive('readLine')->andReturn('sk-ci-key');

    artisan(WIZARD, [
        '--no-interaction' => true,
        '--provider'       => AiProvider::Custom->value,
        '--models'         => 'llama-3',
        '--key-stdin'      => true,
    ])->expectsOutputToContain(trans('admin::app.configuration.platform.message.custom-api-url-required'))->assertFailed();

    artisan(WIZARD, [
        '--no-interaction' => true,
        '--provider'       => AiProvider::Custom->value,
        '--api-url'        => 'http://127.0.0.1/v1',
        '--models'         => 'llama-3',
        '--key-stdin'      => true,
    ])->expectsOutputToContain(trans('admin::app.configuration.platform.message.unsafe-api-url'))->assertFailed();
});

it('lists the platforms without their keys', function () {
    $platform = MagicAIPlatform::factory()->managed()->create(['label' => 'Listed AI', 'api_key' => 'sk-secret-listed']);

    artisan('unopim:magic-ai:platform:list')
        ->expectsOutputToContain('Listed AI')
        ->doesntExpectOutputToContain('sk-secret-listed')
        ->assertSuccessful();

    expect($platform->fresh()->safeApiKey())->toBe('sk-secret-listed');
});

it('edits a platform, keeping its key and turning the managed lock off', function () {
    $platform = MagicAIPlatform::factory()->managed()->create([
        'label'    => 'Hosted AI',
        'provider' => AiProvider::OpenAI->value,
        'api_key'  => 'sk-stored-key',
        'models'   => 'gpt-4o,legacy-model',
    ]);

    fakeDiscovery(['gpt-4o', 'gpt-4o-mini']);

    $command = answerConnection(artisan('unopim:magic-ai:platform:edit', ['id' => $platform->id]), 'Hosted AI renamed', '')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o-mini'])
        ->expectsQuestion(wizardLabel('command.models-additional'), '');

    answerFlags($command, default: false, managed: false)->assertSuccessful()->run();

    $platform->refresh();

    expect(MagicAIPlatform::whereIn('label', ['Hosted AI', 'Hosted AI renamed'])->count())->toBe(1)
        ->and($platform->label)->toBe('Hosted AI renamed')
        ->and($platform->safeApiKey())->toBe('sk-stored-key')
        ->and($platform->models)->toBe('gpt-4o-mini')
        ->and($platform->is_managed)->toBeFalse();
});

it('asks which platform to edit when no id is given', function () {
    $platform = MagicAIPlatform::factory()->create(['provider' => AiProvider::OpenAI->value, 'api_key' => 'sk-stored-key']);

    fakeDiscovery(['gpt-4o']);

    $command = artisan('unopim:magic-ai:platform:edit')
        ->expectsQuestion(wizardLabel('command.select-platform'), (string) $platform->id);

    $command = answerConnection($command, 'Picked AI', 'sk-new-key')
        ->expectsQuestion(wizardLabel('fields.models'), ['gpt-4o'])
        ->expectsQuestion(wizardLabel('command.models-additional'), '');

    answerFlags($command, default: false)->assertSuccessful()->run();

    expect($platform->fresh()->safeApiKey())->toBe('sk-new-key')
        ->and($platform->fresh()->is_managed)->toBeTrue();
});

it('edits a platform from options without interaction', function () {
    $platform = MagicAIPlatform::factory()->managed()->create(['api_key' => 'sk-stored-key']);

    artisan('unopim:magic-ai:platform:edit', [
        'id'               => $platform->id,
        '--no-interaction' => true,
        '--models'         => 'gpt-4o',
        '--unmanaged'      => true,
    ])->assertSuccessful();

    $platform->refresh();

    expect($platform->safeApiKey())->toBe('sk-stored-key')
        ->and($platform->models)->toBe('gpt-4o')
        ->and($platform->is_managed)->toBeFalse();

    artisan('unopim:magic-ai:platform:edit', ['--no-interaction' => true])
        ->expectsOutputToContain(trans('admin::app.configuration.platform.command.missing-argument', ['argument' => 'id']))
        ->assertFailed();
});

it('deletes a managed platform from the command line', function () {
    $platform = MagicAIPlatform::factory()->managed()->create(['label' => 'To delete']);

    artisan('unopim:magic-ai:platform:delete', ['id' => $platform->id])
        ->expectsConfirmation(trans('admin::app.configuration.platform.command.delete-confirm', ['label' => 'To delete']), 'no')
        ->assertSuccessful();

    expect($platform->fresh())->not->toBeNull();

    artisan('unopim:magic-ai:platform:delete', ['id' => $platform->id])
        ->expectsConfirmation(trans('admin::app.configuration.platform.command.delete-confirm', ['label' => 'To delete']), 'yes')
        ->assertSuccessful();

    expect($platform->fresh())->toBeNull();
});

it('deletes without a prompt only when forced', function () {
    $platform = MagicAIPlatform::factory()->create();

    artisan('unopim:magic-ai:platform:delete', ['id' => $platform->id, '--no-interaction' => true])
        ->expectsOutputToContain(trans('admin::app.configuration.platform.command.force-required'))
        ->assertFailed();

    expect($platform->fresh())->not->toBeNull();

    artisan('unopim:magic-ai:platform:delete', ['id' => $platform->id, '--force' => true])->assertSuccessful();

    expect($platform->fresh())->toBeNull();
});

it('refuses to delete the default platform', function () {
    $platform = MagicAIPlatform::factory()->default()->create();

    artisan('unopim:magic-ai:platform:delete', ['id' => $platform->id, '--force' => true])
        ->expectsOutputToContain(trans('admin::app.configuration.platform.message.cannot-delete-default'))
        ->assertFailed();

    expect($platform->fresh())->not->toBeNull();
});

it('sets the default platform only when it is enabled', function () {
    $previous = MagicAIPlatform::factory()->default()->create();
    $disabled = MagicAIPlatform::factory()->disabled()->create();
    $enabled = MagicAIPlatform::factory()->create();

    artisan('unopim:magic-ai:platform:default', ['id' => $disabled->id])
        ->expectsOutputToContain(trans('admin::app.configuration.platform.message.default-requires-enabled'))
        ->assertFailed();

    expect($disabled->fresh()->is_default)->toBeFalse();

    artisan('unopim:magic-ai:platform:default', ['id' => $enabled->id])->assertSuccessful();

    expect($enabled->fresh()->is_default)->toBeTrue()
        ->and($previous->fresh()->is_default)->toBeFalse();
});
