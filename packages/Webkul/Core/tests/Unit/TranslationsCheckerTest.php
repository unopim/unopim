<?php

use Illuminate\Support\Facades\File;
use Webkul\MagicAI\MagicAI;

/**
 * The command reconciles files in place, so every run is scoped to a throwaway package.
 * Auditing the shipped packages would rewrite their lang files — and a worker killed
 * mid-test would leave those edits in the working tree.
 */
beforeEach(function (): void {
    $this->fixturePackage = 'TranslationFixture'.(getenv('TEST_TOKEN') ?: '0');
    $this->fixtureDir = base_path('packages/Webkul/'.$this->fixturePackage);
    $this->langDir = $this->fixtureDir.'/src/Resources/lang';
    $this->frFile = $this->langDir.'/fr_FR/app.php';

    File::ensureDirectoryExists($this->langDir.'/en_US');
    File::ensureDirectoryExists($this->langDir.'/fr_FR');

    File::put($this->langDir.'/en_US/app.php', <<<'PHP'
        <?php

        return [
            'prompt' => [
                'create' => [
                    'select-purpose' => 'Select purpose',
                    'loading-models' => 'Loading models...',
                ],
            ],
        ];
        PHP);

    File::put($this->frFile, <<<'PHP'
        <?php

        return [
            'prompt' => [
                'create' => [
                    'select-purpose' => 'Sélectionner l\'objectif',
                    'loading-models' => 'Chargement des modèles...',
                ],
            ],
        ];
        PHP);
});

afterEach(function (): void {
    File::deleteDirectory($this->fixtureDir);
});

function mockTranslator(array $expectations = []): void
{
    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault')->andReturnSelf();
    $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mock->shouldReceive('setPrompt')->andReturnSelf();
    $mock->shouldReceive('setTemperature')->andReturnSelf();
    $mock->shouldReceive('setMaxTokens')->andReturnSelf();

    foreach ($expectations as $method => $behaviour) {
        $behaviour($mock->shouldReceive($method));
    }

    app()->instance('magic_ai', $mock);
}

function dropFrenchKey(string $file): void
{
    File::put($file, str_replace("'select-purpose'", "// 'select-purpose-removed'", File::get($file)));
}

it('fails when --translate is used without --fix', function () {
    $this->artisan('unopim:translations:check', [
        '--translate' => true,
        '--package'   => $this->fixturePackage,
    ])->assertFailed()
        ->expectsOutputToContain('The --translate flag requires --fix');
});

it('reports no work when all locales are reconciled', function () {
    $this->artisan('unopim:translations:check', [
        '--fix'     => true,
        '--locale'  => 'fr_FR',
        '--package' => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('Every locale is already reconciled');
});

it('enables AI translation when a default platform is configured', function () {
    mockTranslator();

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('AI translation:');
});

it('aborts with --translate when no AI platform is configured and no --fallback', function () {
    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andThrow(new RuntimeException('No platform'));

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertFailed()
        ->expectsOutputToContain('No default AI platform configured')
        ->expectsOutputToContain('--fallback');
});

it('falls back to English copy with --translate --fallback when no AI platform', function () {
    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andThrow(new RuntimeException('No platform'));

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--fallback'  => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('--fallback enabled');
});

it('translates absent keys via AI when platform is configured', function () {
    dropFrenchKey($this->frFile);

    mockTranslator(['ask' => fn ($e) => $e->andReturn(json_encode([
        'prompt.create.select-purpose' => "Sélectionner l'objectif",
    ], JSON_UNESCAPED_UNICODE))]);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('AI translation:')
        ->expectsOutputToContain('translated');

    expect(data_get(include $this->frFile, 'prompt.create.select-purpose'))
        ->toBe("Sélectionner l'objectif");
});

it('skips file when AI returns invalid JSON without --fallback', function () {
    dropFrenchKey($this->frFile);

    mockTranslator(['ask' => fn ($e) => $e->andReturn('Not valid JSON')]);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('Skipping file');
});

it('copies English with --fallback when AI returns invalid JSON', function () {
    dropFrenchKey($this->frFile);

    mockTranslator(['ask' => fn ($e) => $e->andReturn('Not valid JSON')]);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--fallback'  => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('--fallback: copying English values');

    expect(data_get(include $this->frFile, 'prompt.create.select-purpose'))->toBe('Select purpose');
});

it('handles AI exception and skips without --fallback', function () {
    dropFrenchKey($this->frFile);

    mockTranslator(['ask' => fn ($e) => $e->andThrow(new RuntimeException('API rate limit exceeded'))]);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('API rate limit exceeded')
        ->expectsOutputToContain('Skipping file');
});

it('extracts JSON from markdown code fences in AI response', function () {
    dropFrenchKey($this->frFile);

    mockTranslator(['ask' => fn ($e) => $e->andReturn("```json\n".json_encode([
        'prompt.create.select-purpose' => "Sélectionner l'objectif",
    ], JSON_UNESCAPED_UNICODE)."\n```")]);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('translated');

    expect(data_get(include $this->frFile, 'prompt.create.select-purpose'))
        ->toBe("Sélectionner l'objectif");
});

it('fails when --fix-untranslated is used without --fix --translate', function () {
    $this->artisan('unopim:translations:check', [
        '--fix-untranslated' => true,
        '--package'          => $this->fixturePackage,
    ])->assertFailed()
        ->expectsOutputToContain('The --fix-untranslated flag requires --fix --translate');
});

it('fails when --fix-untranslated is used with --fix but without --translate', function () {
    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--fix-untranslated' => true,
        '--package'          => $this->fixturePackage,
    ])->assertFailed()
        ->expectsOutputToContain('The --fix-untranslated flag requires --fix --translate');
});

it('detects and re-translates untranslated keys via AI', function () {
    File::put($this->frFile, str_replace(
        "'Chargement des modèles...'",
        "'Loading models...'",
        File::get($this->frFile)
    ));

    mockTranslator(['ask' => fn ($e) => $e->andReturn(json_encode([
        'prompt.create.loading-models' => 'Chargement des modèles...',
    ], JSON_UNESCAPED_UNICODE))]);

    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--translate'        => true,
        '--fix-untranslated' => true,
        '--locale'           => 'fr_FR',
        '--package'          => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('untranslated re-translated');

    expect(data_get(include $this->frFile, 'prompt.create.loading-models'))
        ->toBe('Chargement des modèles...');
});

it('shows fix-untranslated enabled indicator when flag is used', function () {
    mockTranslator();

    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--translate'        => true,
        '--fix-untranslated' => true,
        '--locale'           => 'fr_FR',
        '--package'          => $this->fixturePackage,
    ])->assertSuccessful()
        ->expectsOutputToContain('Fix untranslated:');
});
