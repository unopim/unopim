<?php

use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\Services\PromptBuilder;

describe('PromptBuilder static-prefix ordering for prompt caching (Issue #421)', function () {

    beforeEach(function () {
        $this->builder = new PromptBuilder;
    });

    it('emits the static system prompt first and the dynamic user instruction last', function () {
        $payload = new AgentPayload(
            agentId: 1,
            credentialId: 1,
            instruction: 'Enrich the product title.',
            context: ['sku' => 'ABC-123', 'name' => 'Widget'],
            metadata: ['systemPrompt' => 'You are a PIM enrichment assistant.'],
        );

        $messages = $this->builder->build($payload);

        expect($messages)->toHaveCount(3)
            ->and($messages[0]['role'])->toBe('system')
            ->and($messages[0]['content'])->toBe('You are a PIM enrichment assistant.')
            ->and($messages[1]['role'])->toBe('system')
            ->and($messages[1]['content'])->toStartWith('Context data:')
            ->and($messages[2]['role'])->toBe('user')
            ->and($messages[2]['content'])->toBe('Enrich the product title.');
    });

    it('keeps the user instruction last when no system prompt is configured', function () {
        $payload = new AgentPayload(
            agentId: 1,
            credentialId: 1,
            instruction: 'Do the thing.',
        );

        $messages = $this->builder->build($payload);

        expect($messages)->toHaveCount(1)
            ->and($messages[0])->toBe(['role' => 'user', 'content' => 'Do the thing.']);
    });

    it('serializes identical context deterministically regardless of key order', function () {
        $base = [
            'agentId'      => 1,
            'credentialId' => 1,
            'instruction'  => 'Enrich.',
        ];

        $payloadA = new AgentPayload(...$base, context: [
            'sku'        => 'ABC-123',
            'attributes' => ['color' => 'red', 'brand' => 'Acme'],
        ]);

        $payloadB = new AgentPayload(...$base, context: [
            'attributes' => ['brand' => 'Acme', 'color' => 'red'],
            'sku'        => 'ABC-123',
        ]);

        expect($this->builder->build($payloadA))->toBe($this->builder->build($payloadB));
    });

    it('preserves the order of list (sequential) arrays in context', function () {
        $payload = new AgentPayload(
            agentId: 1,
            credentialId: 1,
            instruction: 'Enrich.',
            context: ['skus' => ['Z-1', 'A-2', 'M-3']],
        );

        $messages = (new PromptBuilder)->build($payload);

        expect($messages[0]['content'])->toContain('"Z-1"')
            ->and(strpos($messages[0]['content'], '"Z-1"'))
            ->toBeLessThan(strpos($messages[0]['content'], '"A-2"'));
    });
});
