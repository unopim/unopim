<?php

use Webkul\MagicAI\MagicAI;

it('fails when --translate is used without --fix', function () {
    $this->artisan('unopim:translations:check', [
        '--translate' => true,
    ])->assertFailed()
        ->expectsOutputToContain('The --translate flag requires --fix');
});

it('reports no work when all locales are reconciled', function () {
    $this->artisan('unopim:translations:check', [
        '--fix'    => true,
        '--locale' => 'fr_FR',
    ])->assertSuccessful()
        ->expectsOutputToContain('Every locale is already reconciled');
});

it('enables AI translation when a default platform is configured', function () {
    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
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
    ])->assertSuccessful()
        ->expectsOutputToContain('--fallback enabled');
});

it('translates absent keys via AI when platform is configured', function () {
    // Remove some keys from fr_FR to create absent keys
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    // Remove 'select-purpose' key to create an absent key
    $modified = str_replace(
        "'select-purpose'",
        "// 'select-purpose-removed'",
        $originalContent
    );
    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();
    $mockMagicAI->shouldReceive('ask')->andReturn(json_encode([
        'configuration.prompt.create.select-purpose' => "Sélectionner l'objectif",
    ], JSON_UNESCAPED_UNICODE));

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('AI translation:')
        ->expectsOutputToContain('translated');

    // Verify the key was translated, not English-copied
    $result = include $frFile;
    $selectPurpose = data_get($result, 'configuration.prompt.create.select-purpose');
    expect($selectPurpose)->toBe("Sélectionner l'objectif");

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('skips file when AI returns invalid JSON without --fallback', function () {
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    // Remove a key to trigger translation
    $modified = str_replace(
        "'select-purpose'",
        "// 'select-purpose-removed'",
        $originalContent
    );
    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();
    $mockMagicAI->shouldReceive('ask')->andReturn('Not valid JSON');

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('Skipping file');

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('copies English with --fallback when AI returns invalid JSON', function () {
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    // Remove a key to trigger translation
    $modified = str_replace(
        "'select-purpose'",
        "// 'select-purpose-removed'",
        $originalContent
    );
    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();
    $mockMagicAI->shouldReceive('ask')->andReturn('Not valid JSON');

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--fallback'  => true,
        '--locale'    => 'fr_FR',
        '--package'   => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('--fallback: copying English values');

    // Verify the key was filled with English value as fallback
    $result = include $frFile;
    $selectPurpose = data_get($result, 'configuration.prompt.create.select-purpose');
    expect($selectPurpose)->toBe('Select purpose'); // English fallback

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('handles AI exception and skips without --fallback', function () {
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    $modified = str_replace(
        "'select-purpose'",
        "// 'select-purpose-removed'",
        $originalContent
    );
    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();
    $mockMagicAI->shouldReceive('ask')->andThrow(new RuntimeException('API rate limit exceeded'));

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('API rate limit exceeded')
        ->expectsOutputToContain('Skipping file');

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('extracts JSON from markdown code fences in AI response', function () {
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    $modified = str_replace(
        "'select-purpose'",
        "// 'select-purpose-removed'",
        $originalContent
    );
    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();

    // AI wraps response in markdown code fences
    $mockMagicAI->shouldReceive('ask')->andReturn(
        "```json\n".json_encode([
            'configuration.prompt.create.select-purpose' => "Sélectionner l'objectif",
        ], JSON_UNESCAPED_UNICODE)."\n```"
    );

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'       => true,
        '--translate' => true,
        '--locale'    => 'fr_FR',
        '--package'   => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('translated');

    // Verify translation was applied despite code fences
    $result = include $frFile;
    $selectPurpose = data_get($result, 'configuration.prompt.create.select-purpose');
    expect($selectPurpose)->toBe("Sélectionner l'objectif");

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('fails when --fix-untranslated is used without --fix --translate', function () {
    $this->artisan('unopim:translations:check', [
        '--fix-untranslated' => true,
    ])->assertFailed()
        ->expectsOutputToContain('The --fix-untranslated flag requires --fix --translate');
});

it('fails when --fix-untranslated is used with --fix but without --translate', function () {
    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--fix-untranslated' => true,
    ])->assertFailed()
        ->expectsOutputToContain('The --fix-untranslated flag requires --fix --translate');
});

it('detects and re-translates untranslated keys via AI', function () {
    $frFile = base_path('packages/Webkul/Admin/src/Resources/lang/fr_FR/app.php');
    $originalContent = file_get_contents($frFile);

    // Set a French key to the English value (simulating untranslated — no single quotes in value)
    // Use regex to handle variable whitespace alignment from Pint
    $modified = preg_replace(
        "/('loading-models'\s*=>\s*)'Chargement des modèles\.\.\.'/",
        "$1'Loading models...'",
        $originalContent
    );

    file_put_contents($frFile, $modified);

    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();
    $mockMagicAI->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setPrompt')->andReturnSelf();
    $mockMagicAI->shouldReceive('setTemperature')->andReturnSelf();
    $mockMagicAI->shouldReceive('setMaxTokens')->andReturnSelf();
    $mockMagicAI->shouldReceive('ask')->andReturn(json_encode([
        'configuration.prompt.create.loading-models' => 'Chargement des modèles...',
    ], JSON_UNESCAPED_UNICODE));

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--translate'        => true,
        '--fix-untranslated' => true,
        '--locale'           => 'fr_FR',
        '--package'          => 'Admin',
    ])->assertSuccessful()
        ->expectsOutputToContain('untranslated re-translated');

    // Verify the key was re-translated back to French
    $result = include $frFile;
    $loadingModels = data_get($result, 'configuration.prompt.create.loading-models');
    expect($loadingModels)->toBe('Chargement des modèles...');

    // Restore original file
    file_put_contents($frFile, $originalContent);
});

it('shows fix-untranslated enabled indicator when flag is used', function () {
    $mockMagicAI = Mockery::mock(MagicAI::class);
    $mockMagicAI->shouldReceive('useDefault')->andReturnSelf();

    $this->app->instance('magic_ai', $mockMagicAI);

    $this->artisan('unopim:translations:check', [
        '--fix'              => true,
        '--translate'        => true,
        '--fix-untranslated' => true,
        '--locale'           => 'fr_FR',
    ])->assertSuccessful()
        ->expectsOutputToContain('Fix untranslated:');
});
