<?php

use Webkul\AiAgent\Services\TokenEstimator;

describe('TokenEstimator (Issue #423)', function () {

    beforeEach(function () {
        $this->estimator = new TokenEstimator;
    });

    it('estimates zero tokens for an empty string', function () {
        expect($this->estimator->estimate(''))->toBe(0);
    });

    it('estimates using the chars-per-token divisor, rounding up', function () {
        expect($this->estimator->estimate('abcd'))->toBe(1)
            ->and($this->estimator->estimate('abcde'))->toBe(2)
            ->and($this->estimator->estimate(str_repeat('a', 400)))->toBe(100);
    });

    it('counts bytes so multibyte input estimates conservatively', function () {
        // "日本語" is 3 characters but 9 UTF-8 bytes → ceil(9 / 4) = 3.
        expect($this->estimator->estimate('日本語'))->toBe(3);
    });

    it('adds role and fixed overhead when estimating a message', function () {
        $message = ['role' => 'user', 'content' => str_repeat('a', 40)];

        expect($this->estimator->estimateMessage($message))->toBe(
            TokenEstimator::MESSAGE_OVERHEAD_TOKENS + 1 + 10
        );
    });

    it('json-encodes non-string content before estimating', function () {
        $content = ['type' => 'text', 'text' => 'hello'];

        $expected = TokenEstimator::MESSAGE_OVERHEAD_TOKENS
            + (int) ceil(strlen('user') / TokenEstimator::CHARS_PER_TOKEN)
            + (int) ceil(strlen((string) json_encode($content)) / TokenEstimator::CHARS_PER_TOKEN);

        expect($this->estimator->estimateMessage(['role' => 'user', 'content' => $content]))->toBe($expected);
    });

    it('prices image blocks at a fixed cost instead of their base64 payload', function () {
        $base64 = base64_encode(random_bytes(300000)); // ~400KB payload

        $message = [
            'role'    => 'user',
            'content' => [
                ['type' => 'text', 'text' => 'Analyze this product image'],
                ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,'.$base64]],
            ],
        ];

        $estimate = $this->estimator->estimateMessage($message);

        // Without the fixed image cost the base64 alone would estimate ~100K tokens.
        expect($estimate)->toBeLessThan(TokenEstimator::IMAGE_BLOCK_TOKENS + 100)
            ->and($estimate)->toBeGreaterThanOrEqual(TokenEstimator::IMAGE_BLOCK_TOKENS);
    });

    it('sums per-message estimates for a conversation', function () {
        $messages = [
            ['role' => 'system', 'content' => str_repeat('s', 80)],
            ['role' => 'user', 'content' => str_repeat('u', 120)],
        ];

        $expected = $this->estimator->estimateMessage($messages[0])
            + $this->estimator->estimateMessage($messages[1]);

        expect($this->estimator->estimateMessages($messages))->toBe($expected)
            ->and($this->estimator->estimateMessages([]))->toBe(0);
    });
});
