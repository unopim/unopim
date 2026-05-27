<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Webkul\Admin\Http\Controllers\MagicAI\MagicAIController;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Repository\MagicAISystemPromptRepository;
use Webkul\MagicAI\Repository\MagicPromptRepository;
use Webkul\MagicAI\Services\Prompt\Prompt;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Regression: when the configured platform id (in core_config) or the
 * platform_id sent by the frontend points to a deleted platform, the
 * controllers must fall back to the default platform — never blow up
 * with ModelNotFoundException.
 */
beforeEach(function () {
    $this->loginAsAdmin();

    // Wipe ALL rows for these codes (dev DBs may have channel/locale-scoped
    // duplicates). Then insert a single canonical "0" so the controller's
    // resolvePlatform takes the useDefault() branch instead of loading a
    // stale id that the mock can't predict.
    foreach ([
        'general.magic_ai.settings.ai_platform',
        'general.magic_ai.translation.ai_platform',
    ] as $code) {
        DB::table('core_config')->where('code', $code)->delete();
        DB::table('core_config')->insert([
            'code'  => $code,
            'value' => '0',
        ]);
    }

    $promptService = new class extends Prompt
    {
        public function getPrompt(string $prompt, ?int $resourceId = null, ?string $resourceType = null): string
        {
            return $prompt;
        }
    };
    $this->app->instance(Prompt::class, $promptService);
});

it('falls back to default when configured text platform id is deleted', function () {
    MagicAIPlatform::query()->delete();
    MagicAIPlatform::create([
        'label'      => 'Default Platform',
        'provider'   => 'openai',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4o-mini',
        'is_default' => true,
        'status'     => true,
    ]);

    DB::table('core_config')->updateOrInsert(
        ['code' => 'general.magic_ai.settings.ai_platform'],
        ['value' => '99999']
    );
    app('config')->set('core_config', null);

    $mock = Mockery::mock(MagicAI::class);
    // Match any platform id — covers both the expected 99999 from our config
    // and any stale id (e.g. 3) that may pre-exist in the dev DB.
    $mock->shouldReceive('setPlatformId')->andThrow(new ModelNotFoundException);
    $mock->shouldReceive('useDefault')->atLeast()->once()->andReturnSelf();
    $mock->shouldReceive('setModel')->andReturnSelf();
    $mock->shouldReceive('setTemperature')->andReturnSelf();
    $mock->shouldReceive('setMaxTokens')->andReturnSelf();
    $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
    $mock->shouldReceive('setPrompt')->andReturnSelf();
    $mock->shouldReceive('ask')->andReturn('ok');
    $this->app->instance('magic_ai', $mock);

    $this->postJson(route('admin.magic_ai.content'), [
        'model'              => 'gpt-4o-mini',
        'prompt'             => 'hi',
        'system_prompt_text' => '',
    ])->assertOk();
});

it('falls back to default when request platform_id is deleted', function () {
    // Direct unit test on the controller's setPlatformOrDefault helper —
    // avoids the flaky mocked-facade cross-suite interaction we saw when
    // routing this through a real HTTP request with DatabaseTransactions.
    $controller = new MagicAIController(
        productRepository: app(ProductRepository::class),
        attributeRepository: app(AttributeRepository::class),
        categoryFieldRepository: app(CategoryFieldRepository::class),
        magicPromptRepository: app(MagicPromptRepository::class),
        magicAiSystemPromptRepository: app(MagicAISystemPromptRepository::class),
        platformRepository: app(MagicAIPlatformRepository::class),
        promptService: app(Prompt::class),
    );

    $mock = Mockery::mock(MagicAI::class);
    $mock->shouldReceive('useDefault')->once()->andReturnSelf();
    $mock->shouldReceive('setPlatformId')->with(99999)->andThrow(new ModelNotFoundException);
    $this->app->instance('magic_ai', $mock);

    $reflection = new ReflectionMethod($controller, 'setPlatformOrDefault');
    $reflection->setAccessible(true);
    $result = $reflection->invoke($controller, 99999);

    expect($result)->toBe($mock);
});

it('returns clean 404 JSON when updating a deleted platform', function () {
    $response = $this->putJson(route('admin.magic_ai.platform.update', 99999), [
        'label'    => 'Ghost',
        'provider' => 'openai',
        'models'   => 'gpt-4o-mini',
    ]);

    $response->assertNotFound()
        ->assertJsonStructure(['message']);

    expect($response->json('message'))->not->toBeEmpty();
});
