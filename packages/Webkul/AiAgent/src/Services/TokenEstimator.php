<?php

namespace Webkul\AiAgent\Services;

/**
 * Heuristic token estimator for pre-flight request sizing.
 *
 * Uses the "~4 characters per token" approximation over the byte length
 * of the text. Byte length (rather than multibyte character count) is
 * intentional: multibyte input tokenizes more densely, so counting bytes
 * keeps the estimate conservative for pre-flight context window checks.
 */
class TokenEstimator
{
    /**
     * Approximate number of characters per token.
     */
    public const CHARS_PER_TOKEN = 4;

    /**
     * Fixed per-message overhead in tokens (role markers, separators, priming).
     */
    public const MESSAGE_OVERHEAD_TOKENS = 4;

    /**
     * Fixed conservative cost for one image content block. Providers charge
     * images by dimensions/tiles (roughly 100–2000 tokens), never by base64
     * payload size — estimating base64 at chars/4 would wildly overcount.
     */
    public const IMAGE_BLOCK_TOKENS = 1600;

    /**
     * Estimate the token count of a plain text string.
     */
    public function estimate(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        return (int) ceil(strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * Estimate the total token count of a chat message array.
     *
     * @param  array<int, array{role?: string, content?: mixed}>  $messages
     */
    public function estimateMessages(array $messages): int
    {
        $total = 0;

        foreach ($messages as $message) {
            $total += $this->estimateMessage($message);
        }

        return $total;
    }

    /**
     * Estimate a single chat message (role + content + fixed overhead).
     *
     * @param  array{role?: string, content?: mixed}  $message
     */
    public function estimateMessage(array $message): int
    {
        return self::MESSAGE_OVERHEAD_TOKENS
            + $this->estimate((string) ($message['role'] ?? ''))
            + $this->estimateContent($message['content'] ?? '');
    }

    /**
     * Estimate message content: plain strings by length, multimodal block
     * lists per block (image blocks at a fixed cost so base64 payloads do
     * not distort the estimate), anything else from its JSON encoding.
     */
    protected function estimateContent(mixed $content): int
    {
        if (is_string($content)) {
            return $this->estimate($content);
        }

        if (! is_array($content) || ! array_is_list($content)) {
            return $this->estimate((string) json_encode($content));
        }

        $total = 0;

        foreach ($content as $block) {
            if (is_array($block) && $this->isImageBlock($block)) {
                $total += self::IMAGE_BLOCK_TOKENS;

                continue;
            }

            $total += $this->estimate(is_string($block) ? $block : (string) json_encode($block));
        }

        return $total;
    }

    /**
     * Detect provider image content blocks (OpenAI, Anthropic, generic).
     *
     * @param  array<mixed>  $block
     */
    protected function isImageBlock(array $block): bool
    {
        return in_array(
            strtolower((string) ($block['type'] ?? '')),
            ['image', 'image_url', 'input_image'],
            true,
        );
    }
}
