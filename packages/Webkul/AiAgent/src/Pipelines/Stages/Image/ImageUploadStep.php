<?php

namespace Webkul\AiAgent\Pipelines\Stages\Image;

use Webkul\AiAgent\Contracts\PipelineStageContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\PipelineException;

/**
 * Stage 1 — Validates the image source and normalises it to a
 * base64 data URI (for file paths) or a plain URL (for remote images).
 *
 * Reads from:
 *   $payload->context['imageSource']  — URL, absolute file path, or base64 string
 *
 * Writes to metadata:
 *   imageSource    string  Original source value
 *   imageType      string  'url' | 'file' | 'base64'
 *   imageContent   string  Normalised value passed to subsequent stages
 *   imageMimeType  string  MIME type (image/jpeg, image/png, image/webp, image/gif)
 *   imageSize      int     Byte size (0 for remote URLs)
 */
class ImageUploadStep implements PipelineStageContract
{
    /**
     * Allowed MIME types for product images.
     *
     * @var array<string>
     */
    protected const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Maximum local file size in bytes (10 MB).
     */
    protected const MAX_FILE_BYTES = 10 * 1024 * 1024;

    /**
     * {@inheritdoc}
     *
     * @throws PipelineException
     */
    public function handle(AgentPayload $payload, \Closure $next): AgentPayload
    {
        $imageSource = $payload->context['imageSource'] ?? null;

        if (empty($imageSource)) {
            throw new PipelineException(
                'ImageUploadStep: imageSource is required in payload context.',
                self::class,
            );
        }

        [$imageType, $imageContent, $mimeType, $byteSize] = $this->normalise($imageSource);

        $ctx = ImageProductContext::fromArray($payload->metadata['imageContext'] ?? [])
            ->withImagePath($imageContent);

        return $next($payload->withMetadata([
            'imageContext'  => $ctx->toArray(),
            // Legacy flat keys kept for backward-compat with non-DTO consumers
            'imageSource'   => $imageSource,
            'imageType'     => $imageType,
            'imageContent'  => $imageContent,
            'imageMimeType' => $mimeType,
            'imageSize'     => $byteSize,
        ]));
    }

    /**
     * Detect image type and return a normalised representation.
     *
     * @return array{string, string, string, int} [type, content, mimeType, byteSize]
     *
     * @throws PipelineException
     */
    protected function normalise(string $source): array
    {
        if ($this->isUrl($source)) {
            if (filter_var($source, FILTER_VALIDATE_URL) === false) {
                throw new PipelineException("ImageUploadStep: invalid URL — $source", self::class);
            }

            return ['url', $source, 'image/jpeg', 0];
        }

        if ($this->isDataUri($source)) {
            [$mimeType] = $this->parseDataUri($source);
            $this->assertAllowedMime($mimeType);

            return ['base64', $source, $mimeType, strlen($source)];
        }

        if (file_exists($source)) {
            return $this->readLocalFile($source);
        }

        throw new PipelineException(
            "ImageUploadStep: source is not a valid URL, data URI, or file path — $source",
            self::class,
        );
    }

    /**
     * Read, validate, and base64-encode a local file.
     *
     * @return array{string, string, string, int}
     *
     * @throws PipelineException
     */
    protected function readLocalFile(string $path): array
    {
        $size = filesize($path);

        if ($size === false || $size > self::MAX_FILE_BYTES) {
            throw new PipelineException(
                sprintf('ImageUploadStep: file too large (%s bytes, max %s) — %s', $size, self::MAX_FILE_BYTES, $path),
                self::class,
            );
        }

        $mimeType = mime_content_type($path);

        $this->assertAllowedMime($mimeType);

        $raw = file_get_contents($path);
        $content = 'data:'.$mimeType.';base64,'.base64_encode($raw);

        return ['file', $content, $mimeType, $size];
    }

    /**
     * Parse a data URI and return [mimeType, base64Data].
     *
     * @return array{string, string}
     */
    protected function parseDataUri(string $uri): array
    {
        preg_match('#^data:([^;]+);base64,(.+)$#s', $uri, $m);

        return [$m[1] ?? 'application/octet-stream', $m[2] ?? ''];
    }

    /**
     * @throws PipelineException
     */
    protected function assertAllowedMime(string $mimeType): void
    {
        if (! in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new PipelineException(
                "ImageUploadStep: unsupported MIME type '$mimeType'. Allowed: ".implode(', ', self::ALLOWED_MIMES),
                self::class,
            );
        }
    }

    protected function isUrl(string $source): bool
    {
        return preg_match('#^https?://#i', $source) === 1;
    }

    protected function isDataUri(string $source): bool
    {
        return str_starts_with($source, 'data:');
    }
}
