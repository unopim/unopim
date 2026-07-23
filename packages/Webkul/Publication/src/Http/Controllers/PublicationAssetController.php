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
     * Serves a document from the private asset disk, but only a path the
     * current Published version's payload actually references.
     *
     * Reads the `type` route default via the Request rather than as a method
     * parameter: ControllerDispatcher binds non-class parameters positionally
     * (see PublicationController::routeType()'s docblock for the mechanics),
     * and `defaults()` values are appended after real URI captures — a
     * `string $type` parameter here would silently receive `{uuid}`'s value.
     */
    public function show(Request $request, string $uuid, string $path): Response
    {
        $type = (string) $request->route('type');

        $publication = $this->resolver->findPublication($uuid, $type);

        // Narrower than the page itself: a withdrawn or redacted passport
        // still renders a tombstone (PublicationStatus::isPubliclyResolvable()),
        // but a downloadable certificate/report is not "last published data
        // kept visible" the same way rendered text is — it stops the instant
        // the publication is no longer actively Published.
        abort_if($publication === null || $publication->status !== PublicationStatus::Published, 404);

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
     * Rejects before Storage is ever touched: `..`, a leading slash or
     * backslash, an embedded backslash anywhere, control characters (which
     * catch an embedded newline as much as they catch a null byte), and
     * anything outside a conservative allow-list of characters. Flysystem's
     * own traversal guard throws rather than returning false, and
     * `FilesystemAdapter::exists()` has no try/catch around that throw — this
     * check exists so a malformed path never reaches Flysystem at all.
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

        // Includes `_`: locale codes stamped into a document path by the
        // payload builder (e.g. `publication/17/en_US/certificate.pdf`) always
        // contain one.
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
     * The one indexed query this proxy runs per request: whether the current
     * Published version of any locale for this publication references $path.
     */
    private function isReferenced(int $publicationId, string $path): bool
    {
        return PublicationVersionDocumentProxy::modelClass()::query()
            ->where('publication_id', $publicationId)
            ->where('path', $path)
            ->exists();
    }
}
