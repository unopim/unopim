<?php

use Webkul\ProductPassport\Http\Resources\PassportJsonLdResource;
use Webkul\ProductPassport\Services\PassportPayloadBuilder;
use Webkul\ProductPassport\Services\TemplateReadinessGate;

return [
    'types' => [
        'dpp' => [
            'label'           => 'passport::app.type.label',
            'payload_builder' => PassportPayloadBuilder::class,
            'template'        => 'passport::public.passport',
            'route_prefix'    => 'p',
            'gate'            => TemplateReadinessGate::class,
            'jsonld'          => PassportJsonLdResource::class,
        ],
    ],

    /*
     * ESPR access tiers. This whole file is merged into the `publication` config
     * namespace by ProductPassportServiceProvider, so these read as
     * `config('publication.tiers.*')`, never `passport.tiers.*`.
     *
     * The list and its order are code-owned because the signed-URL elevation and
     * the fail-closed clamp in `PublicationController` depend on them; which tier
     * gates a field is chosen per field on the passport template.
     */
    'tiers' => [
        'default' => 'consumer',
        'order'   => ['consumer', 'operator', 'authority'],
    ],
];
