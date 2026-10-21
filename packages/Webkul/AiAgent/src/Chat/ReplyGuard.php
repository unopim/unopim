<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Deterministic checks on a finished assistant reply, independent of the model.
 *
 * A tool call is optional at every step, so the model occasionally presents a
 * plausible product (edit link, image path) it never looked up. Only artifacts
 * that can be verified cheaply are checked: product edit links must point at a
 * product that exists, a product presented without any tool call this turn
 * must already appear in the conversation, and inline images must resolve to a
 * stored file. Free-text tokens (SKUs, numbers, codes) are never judged, since
 * they cannot be told apart from years, prices or proposed new SKUs.
 */
class ReplyGuard
{
    /**
     * Matches a markdown image: ![alt](url "optional title").
     */
    protected const IMAGE_PATTERN = '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"[^"]*")?\)/';

    /**
     * Run every check on the reply.
     *
     * @param  array<int, string>|null  $toolsUsed  Tool names called this turn, or null when unknown.
     * @param  array<int, array{role?: string, content?: string}>  $history  Earlier turns of the conversation.
     */
    public function apply(string $text, ?array $toolsUsed, array $history = []): string
    {
        return $this->stripUnresolvableImages($this->guardProductGrounding($text, $toolsUsed, $history));
    }

    /**
     * Replace the reply with an honest fallback when it presents a product that is not real.
     *
     * The reply is replaced when an edit link points at a product id that does
     * not exist (resolved with one query for all linked ids), or when no tool
     * ran this turn and the reply presents a product link or stored image that
     * does not already appear in the conversation history. A null tool list
     * (unknown) skips the second check.
     *
     * @param  array<int, string>|null  $toolsUsed
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    public function guardProductGrounding(string $text, ?array $toolsUsed, array $history = []): string
    {
        $linkedIds = $this->linkedProductIds($text);
        $missingIds = array_values(array_diff($linkedIds, $this->existingProductIds($linkedIds)));

        if ($missingIds !== []) {
            Log::warning('AI Agent replaced reply linking a non-existent product', ['product_ids' => $missingIds]);

            return trans('ai-agent::app.common.ungrounded-product-reply');
        }

        if ($toolsUsed === [] && $this->presentsUnseenProduct($text, $history)) {
            Log::warning('AI Agent replaced ungrounded product reply (no tools called)');

            return trans('ai-agent::app.common.ungrounded-product-reply');
        }

        return $text;
    }

    /**
     * Replace markdown images whose URL does not resolve to a stored file with a short note.
     *
     * Same-origin /storage URLs are checked on the public disk and URLs on the
     * configured S3 host on the s3 disk. Anything that cannot be checked cheaply
     * is left alone; the widget's host allowlist is the backstop for those.
     */
    public function stripUnresolvableImages(string $text): string
    {
        if (! str_contains($text, '![')) {
            return $text;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: null;
        $s3Host = parse_url((string) (config('filesystems.disks.s3.url') ?: ''), PHP_URL_HOST) ?: null;

        return preg_replace_callback(
            self::IMAGE_PATTERN,
            function (array $match) use ($appHost, $s3Host): string {
                if ($this->imageUrlResolves($match[2], $appHost, $s3Host) !== false) {
                    return $match[0];
                }

                Log::warning('AI Agent stripped unresolvable chat image', ['url' => $match[2]]);

                return $match[1] !== ''
                    ? trans('ai-agent::app.common.image-unavailable-with-alt', ['alt' => $match[1]])
                    : trans('ai-agent::app.common.image-unavailable');
            },
            $text
        ) ?? $text;
    }

    /**
     * Product ids referenced by admin product edit links in the reply.
     *
     * The pattern is derived from the named route, so it follows a changed
     * admin prefix or route path.
     *
     * @return array<int, int>
     */
    public function linkedProductIds(string $text): array
    {
        if (! preg_match_all($this->editLinkPattern(), $text, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map(intval(...), $matches[1])));
    }

    /**
     * The subset of the given product ids that exist.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    protected function existingProductIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(intval(...))
            ->all();
    }

    /**
     * Whether the reply presents a product link or stored image that no earlier turn contained.
     *
     * @param  array<int, array{role?: string, content?: string}>  $history
     */
    protected function presentsUnseenProduct(string $text, array $history): bool
    {
        preg_match_all($this->editLinkPattern(), $text, $links);
        preg_match_all(self::IMAGE_PATTERN, $text, $images);

        $references = array_merge(
            $links[0],
            array_filter($images[2], fn (string $url): bool => str_contains($url, '/storage/')),
        );

        if ($references === []) {
            return false;
        }

        $seen = implode("\n", array_map(
            fn ($turn): string => is_array($turn) ? (string) ($turn['content'] ?? '') : '',
            $history,
        ));

        foreach ($references as $reference) {
            if (! str_contains($seen, $reference)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Regex matching the path of a product edit link and capturing the id.
     */
    protected function editLinkPattern(): string
    {
        $path = route('admin.catalog.products.edit', ['id' => '__ID__'], false);

        return '#'.str_replace('__ID__', '(\d+)', preg_quote($path, '#')).'(?!\d)#';
    }

    /**
     * Whether an image URL points at a file that actually exists.
     *
     * @return bool|null true = exists, false = checked and missing, null = not cheaply checkable
     */
    protected function imageUrlResolves(string $url, ?string $appHost, ?string $s3Host): ?bool
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        $host = $parts['host'] ?? null;
        $path = $parts['path'] ?? '';

        if ($host === null || $host === $appHost) {
            if (preg_match('#/storage/(.+)$#', $path, $match)) {
                return Storage::disk('public')->exists(urldecode($match[1]));
            }

            return null;
        }

        if ($s3Host === null || $host !== $s3Host) {
            return null;
        }

        $key = urldecode(ltrim($path, '/'));

        try {
            return Storage::disk('s3')->exists($key)
                || Storage::disk('s3')->exists((string) preg_replace('#^[^/]+/#', '', $key));
        } catch (\Throwable) {
            return null;
        }
    }
}
