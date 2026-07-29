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
     * Emits an SVG QR code of the passport's public URL, gated by the same resolvable-status and channel-enabled checks.
     * `type` is read via the Request, not a parameter: route defaults are appended after URI captures.
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

        // `alias_identifier` carries the GS1 Digital Link when populated; otherwise the plain passport URL is encoded.
        $target = $publication->alias_identifier
            ?: route('publication.public.'.$type.'.show', ['uuid' => $uuid]);

        $writer = new Writer(new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd));

        return response($writer->writeString($target))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
