<?php

namespace Webkul\Publication\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Publication\Services\PublicationResolver;

class PublicationCarrierController extends Controller
{
    public function __construct(private readonly PublicationResolver $resolver) {}

    /**
     * Emits an SVG QR code encoding the passport's canonical public URL. Gated
     * behind the same publicly-resolvable-status and channel-enabled checks the
     * page/asset controllers apply, so a non-public passport never yields a
     * scannable carrier. The `type` arrives as a route default, read via the
     * Request rather than a parameter: `defaults()` values are appended after
     * URI captures, so a `string $type` parameter would receive `{uuid}`.
     */
    public function show(Request $request, string $uuid): Response
    {
        $type = (string) $request->route('type');

        $publication = $this->resolver->findPublication($uuid, $type);

        if (
            $publication === null
            || ! $publication->status->isPubliclyResolvable()
            || ! $this->resolver->isChannelEnabled($publication)
        ) {
            return response()->view('publication::errors.404', [], 404);
        }

        // `alias_identifier` carries the GS1 Digital Link once Task 6 populates
        // it; until then the plain passport URL is encoded, so the two tasks
        // compose without reordering.
        $target = $publication->alias_identifier
            ?: route('publication.public.'.$type.'.show', ['uuid' => $uuid]);

        $writer = new Writer(new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd));

        return response($writer->writeString($target))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
