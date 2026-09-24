<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\PendingCommand;
use Webkul\MagicAI\Console\Support\StdinReader;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\ModelDiscovery;

use function Pest\Laravel\artisan;

const WIZARD = 'unopim:magic-ai:add-platform';

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
