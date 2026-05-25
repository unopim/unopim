<?php

use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Models\MagicPrompt;
use Webkul\MagicAI\Services\Prompt\Prompt;

beforeEach(function () {
    $this->loginAsAdmin();

    $promptService = new class extends Prompt
    {
        public function getPrompt(string $prompt, ?int $resourceId = null, ?string $resourceType = null): string
        {
            return $prompt;
        }
    };
    $this->app->instance(Prompt::class, $promptService);
});

describe('content endpoint', function () {
    it('returns generated content from the AI provider', function () {
        $mock = Mockery::mock(MagicAI::class);
        $mock->shouldReceive('useDefault')->andReturnSelf();
        $mock->shouldReceive('setPlatformId')->andReturnSelf();
        $mock->shouldReceive('setModel')->with('gpt-4o-mini')->andReturnSelf();
        $mock->shouldReceive('setTemperature')->andReturnSelf();
        $mock->shouldReceive('setMaxTokens')->andReturnSelf();
        $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
        $mock->shouldReceive('setPrompt')->andReturnSelf();
        $mock->shouldReceive('ask')->once()->andReturn('Generated description.');
        $this->app->instance('magic_ai', $mock);

        $this->postJson(route('admin.magic_ai.content'), [
            'model'               => 'gpt-4o-mini',
            'prompt'              => 'Describe a red shoe.',
            'system_prompt_text'  => 'You are a helpful copywriter.',
            'temperature'         => 0.5,
            'max_tokens'          => 500,
        ])
            ->assertOk()
            ->assertJson(['content' => 'Generated description.']);
    });

    it('rejects requests missing the model field', function () {
        $this->postJson(route('admin.magic_ai.content'), [
            'prompt' => 'A prompt without a model.',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['model']);
    });

    it('rejects requests missing the prompt field', function () {
        $this->postJson(route('admin.magic_ai.content'), [
            'model' => 'gpt-4o-mini',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    });

    it('returns a friendly message when the provider times out', function () {
        $mock = Mockery::mock(MagicAI::class);
        $mock->shouldReceive('useDefault')->andReturnSelf();
        $mock->shouldReceive('setPlatformId')->andReturnSelf();
        $mock->shouldReceive('setModel')->andReturnSelf();
        $mock->shouldReceive('setTemperature')->andReturnSelf();
        $mock->shouldReceive('setMaxTokens')->andReturnSelf();
        $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
        $mock->shouldReceive('setPrompt')->andReturnSelf();
        $mock->shouldReceive('ask')->andThrow(new RuntimeException('cURL error 28: Operation timed out'));
        $this->app->instance('magic_ai', $mock);

        $this->postJson(route('admin.magic_ai.content'), [
            'model'              => 'gpt-4o-mini',
            'prompt'             => 'Slow prompt.',
            'system_prompt_text' => 'system',
        ])
            ->assertStatus(400)
            ->assertJson([
                'message' => 'The AI response is taking longer than expected. Please try again.',
            ]);
    });

    it('surfaces other provider errors as 400 with the original message', function () {
        $mock = Mockery::mock(MagicAI::class);
        $mock->shouldReceive('useDefault')->andReturnSelf();
        $mock->shouldReceive('setPlatformId')->andReturnSelf();
        $mock->shouldReceive('setModel')->andReturnSelf();
        $mock->shouldReceive('setTemperature')->andReturnSelf();
        $mock->shouldReceive('setMaxTokens')->andReturnSelf();
        $mock->shouldReceive('setSystemPrompt')->andReturnSelf();
        $mock->shouldReceive('setPrompt')->andReturnSelf();
        $mock->shouldReceive('ask')->andThrow(new RuntimeException('Invalid API key'));
        $this->app->instance('magic_ai', $mock);

        $this->postJson(route('admin.magic_ai.content'), [
            'model'              => 'gpt-4o-mini',
            'prompt'             => 'Any prompt.',
            'system_prompt_text' => 'system',
        ])
            ->assertStatus(400)
            ->assertJson(['message' => 'Invalid API key']);
    });
});

describe('image endpoint', function () {
    it('returns generated image URLs from the AI provider', function () {
        $mock = Mockery::mock(MagicAI::class);
        $mock->shouldReceive('useDefault')->andReturnSelf();
        $mock->shouldReceive('setPlatformId')->andReturnSelf();
        $mock->shouldReceive('setModel')->with('dall-e-3')->andReturnSelf();
        $mock->shouldReceive('setPrompt')->andReturnSelf();
        $mock->shouldReceive('images')->once()->andReturn([
            'https://example.com/image-1.png',
        ]);
        $this->app->instance('magic_ai', $mock);

        $this->postJson(route('admin.magic_ai.image'), [
            'model'  => 'dall-e-3',
            'prompt' => 'A red shoe on a white background.',
            'size'   => '1024x1024',
        ])
            ->assertOk()
            ->assertJson(['images' => ['https://example.com/image-1.png']]);
    });

    it('rejects an unsupported image size', function () {
        $this->postJson(route('admin.magic_ai.image'), [
            'model'  => 'dall-e-3',
            'prompt' => 'a prompt',
            'size'   => '512x512',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['size']);
    });

    it('rejects requests missing the prompt field', function () {
        $this->postJson(route('admin.magic_ai.image'), [
            'model' => 'dall-e-3',
            'size'  => '1024x1024',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    });

    it('returns 500 when the provider raises an exception', function () {
        $mock = Mockery::mock(MagicAI::class);
        $mock->shouldReceive('useDefault')->andReturnSelf();
        $mock->shouldReceive('setPlatformId')->andReturnSelf();
        $mock->shouldReceive('setModel')->andReturnSelf();
        $mock->shouldReceive('setPrompt')->andReturnSelf();
        $mock->shouldReceive('images')->andThrow(new RuntimeException('Quota exceeded'));
        $this->app->instance('magic_ai', $mock);

        $this->postJson(route('admin.magic_ai.image'), [
            'model'  => 'dall-e-3',
            'prompt' => 'a prompt',
            'size'   => '1024x1024',
        ])
            ->assertStatus(500)
            ->assertJson(['message' => 'Quota exceeded']);
    });
});

describe('prompt CRUD', function () {
    it('creates a new prompt', function () {
        $this->postJson(route('admin.magic_ai.prompt.store'), [
            'prompt'  => 'Describe :name in :tone tone',
            'title'   => 'Product description prompt',
            'type'    => 'product',
            'purpose' => 'text_generation',
        ])
            ->assertOk();

        $this->assertDatabaseHas('magic_ai_prompts', [
            'title'   => 'Product description prompt',
            'type'    => 'product',
            'purpose' => 'text_generation',
        ]);
    });

    it('rejects creating a prompt with an unsupported purpose', function () {
        $this->postJson(route('admin.magic_ai.prompt.store'), [
            'prompt'  => 'foo',
            'title'   => 'bar',
            'type'    => 'product',
            'purpose' => 'something_else',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['purpose']);
    });

    it('returns an existing prompt for editing', function () {
        $prompt = MagicPrompt::factory()->create();

        $this->getJson(route('admin.magic_ai.prompt.edit', ['id' => $prompt->id]))
            ->assertOk()
            ->assertJsonPath('data.id', $prompt->id)
            ->assertJsonPath('data.title', $prompt->title);
    });

    it('updates an existing prompt', function () {
        $prompt = MagicPrompt::factory()->create([
            'type'    => 'product',
            'purpose' => 'text_generation',
        ]);

        $this->putJson(route('admin.magic_ai.prompt.update'), [
            'id'      => $prompt->id,
            'prompt'  => 'updated body',
            'title'   => 'updated title',
            'type'    => 'product',
            'purpose' => 'text_generation',
        ])
            ->assertOk();

        $this->assertDatabaseHas('magic_ai_prompts', [
            'id'    => $prompt->id,
            'title' => 'updated title',
        ]);
    });

    it('deletes an existing prompt', function () {
        $prompt = MagicPrompt::factory()->create();

        $this->deleteJson(route('admin.magic_ai.prompt.delete', ['id' => $prompt->id]))
            ->assertOk();

        $this->assertDatabaseMissing('magic_ai_prompts', ['id' => $prompt->id]);
    });
});

describe('default prompts endpoint', function () {
    it('filters prompts by entity type and purpose', function () {
        MagicPrompt::factory()->create([
            'type'    => 'product',
            'purpose' => 'text_generation',
            'title'   => 'product text prompt',
        ]);
        MagicPrompt::factory()->create([
            'type'    => 'category',
            'purpose' => 'text_generation',
            'title'   => 'category text prompt',
        ]);

        $response = $this->getJson(route('admin.magic_ai.default_prompt', [
            'entity_type' => 'product',
            'purpose'     => 'text_generation',
        ]))->assertOk()->json('prompts');

        $titles = collect($response)->pluck('title')->all();

        expect($titles)->toContain('product text prompt')
            ->and($titles)->not->toContain('category text prompt');
    });
});
