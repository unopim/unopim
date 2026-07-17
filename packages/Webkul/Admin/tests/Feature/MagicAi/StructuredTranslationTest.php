<?php

use Webkul\MagicAI\Agents\TranslationAgent;
use Webkul\MagicAI\Contracts\SupportsStructuredTranslation;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\LaravelAiAdapter;

beforeEach(function () {
    $this->platform = MagicAIPlatform::create([
        'label'    => 'Test OpenAI',
        'provider' => 'openai',
        'api_key'  => 'sk-test',
        'models'   => json_encode(['gpt-4o-mini']),
        'status'   => true,
    ]);
});

it('implements the structured translation capability', function () {
    $adapter = new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-4o-mini',
        prompt: 'Translate <p>Hello world</p> into fr_FR',
    );

    expect($adapter)->toBeInstanceOf(SupportsStructuredTranslation::class);
});

it('returns the translated html from the structured response field', function () {
    TranslationAgent::fake([
        ['translated_html' => '<p>Bonjour le monde</p>'],
    ]);

    $result = (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-4o-mini',
        prompt: 'Translate <p>Hello world</p> into fr_FR',
    ))->translate();

    expect($result)->toBe('<p>Bonjour le monde</p>');
});

it('trims whitespace around the translated markup', function () {
    TranslationAgent::fake([
        ['translated_html' => "  <p>Hola</p>\n"],
    ]);

    $result = (new LaravelAiAdapter(
        platform: $this->platform,
        model: 'gpt-4o-mini',
        prompt: 'Translate <p>Hello</p> into es_ES',
    ))->translate();

    expect($result)->toBe('<p>Hola</p>');
});
