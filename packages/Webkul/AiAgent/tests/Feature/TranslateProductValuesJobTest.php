<?php

use Webkul\AiAgent\Jobs\TranslateProductValuesJob;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

beforeEach(function () {
    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'Test Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'gpt-4o-mini',
        'is_default' => true,
        'status'     => true,
    ]);

    $locales = Locale::whereIn('code', ['en_US', 'fr_FR'])->get();
    $locales->each->update(['status' => 1]);

    Channel::where('code', 'default')->firstOrFail()->locales()->sync($locales->pluck('id'));
});

it('names each target locale in the prompt without querying a locales.name column', function () {
    $product = Product::factory()->simple()->create();

    $prompts = [];

    MagicAI::shouldReceive('usePlatform')->andReturnSelf();
    MagicAI::shouldReceive('setTemperature')->andReturnSelf();
    MagicAI::shouldReceive('setMaxTokens')->andReturnSelf();
    MagicAI::shouldReceive('setPrompt')->andReturnUsing(function (string $prompt) use (&$prompts) {
        $prompts[] = $prompt;

        return MagicAI::getFacadeRoot();
    });
    MagicAI::shouldReceive('ask')->andReturn('{"name": "Chaussure"}');

    app()->call([new TranslateProductValuesJob($product->id, 'en_US', ['name' => 'Shoe']), 'handle']);

    expect($prompts)->toHaveCount(1)
        ->and($prompts[0])->toContain('to fr_FR (French (France))');
});
