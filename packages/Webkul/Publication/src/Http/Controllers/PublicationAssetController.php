<?php

namespace Webkul\Publication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;
use Webkul\Publication\Services\PublicationResolver;

class PublicationAssetController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf'  => 'application/pdf',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'webp' => 'image/webp',
        'csv'  => 'text/csv',
        'txt'  => 'text/plain',
    ];

    public function __construct(private readonly PublicationResolver $resolver) {}

    /**
     * Serves a private-disk document, but only a path the current Published version's payload references.
     *
     * `type` is read via the Request, not a parameter: route defaults are appended after URI captures, so a
     * `string $type` parameter would receive `{uuid}`'s value.
     */
    public function show(Request $request, string $uuid, string $path): Response
    {
        $type = (string) $request->route('type');

        $publication = $this->resolver->findPublication($uuid, $type);

        // Narrower than the page: withdrawn/redacted still renders a tombstone, but documents stop at not-Published.
        abort_if($publication === null || $publication->status !== PublicationStatus::Published, 404);

        // Same per-channel kill switch the page enforces: a disabled channel takes its documents off the air too.
        abort_unless($this->resolver->isChannelEnabled($publication), 404);

        $sanitizedPath = $this->sanitizePath($path);

        abort_if($sanitizedPath === null, 404);

        $extension = strtolower(pathinfo($sanitizedPath, PATHINFO_EXTENSION));

        abort_unless(array_key_exists($extension, self::ALLOWED_EXTENSIONS), 404);

        abort_unless($this->isReferenced($publication->id, $sanitizedPath), 404);

        $disk = Storage::disk(config('publication.asset_disk'));

        try {
            abort_unless($disk->exists($sanitizedPath), 404);

            $contents = $disk->get($sanitizedPath);
        } catch (FilesystemException) {
            abort(404);
        }

        return response($contents, 200, [
            'Content-Type'           => self::ALLOWED_EXTENSIONS[$extension],
            'Content-Disposition'    => 'attachment; filename="'.$this->sanitizeFilename(basename($sanitizedPath)).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Rejects traversal, backslashes, control chars, and anything outside the allow-list before Flysystem is touched.
     */
    private function sanitizePath(string $path): ?string
    {
        $decoded = rawurldecode($path);

        if (preg_match('/[\x00-\x1F\x7F]/', $decoded)) {
            return null;
        }

        if (str_contains($decoded, '..') || str_contains($decoded, '\\') || str_starts_with($decoded, '/')) {
            return null;
        }

        // Allow `_`: locale codes stamped into document paths (e.g. `.../en_US/certificate.pdf`) contain one.
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_.\/-]*$/', $decoded)) {
            return null;
        }

        return $decoded;
    }

    private function sanitizeFilename(string $name): string
    {
        return (string) preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
    }

    /**
     * Whether any current Published version for this publication references $path.
     */
    private function isReferenced(int $publicationId, string $path): bool
    {
        return PublicationVersionDocumentProxy::modelClass()::query()
            ->where('publication_id', $publicationId)
            ->where('path', $path)
            ->exists();
    }
}
