<?php

use Webkul\ProductPassport\Http\Resources\PassportJsonLdResource;
use Webkul\ProductPassport\Services\PassportPayloadBuilder;

return [
    'types' => [
        'dpp' => [
            'label'           => 'passport::app.type.label',
            'payload_builder' => PassportPayloadBuilder::class,
            'template'        => 'passport::public.passport',
            'required_group'  => 'dpp',
            'route_prefix'    => 'p',
            'jsonld'          => PassportJsonLdResource::class,
        ],
    ],

    /*
     * ESPR access tiers. This whole file is merged into the `publication`
     * config namespace by ProductPassportServiceProvider, so these read as
     * `config('publication.tiers.*')`, never `passport.tiers.*`.
     *
     * This map is the single source of truth for which tier
     * gates each `dpp_*` field/document — there is no tier column on the
     * attribute. Fields absent from `map` inherit `default`, so an empty map is
     * fully backward-compatible: every field stays `consumer`. Compliance
     * evidence and supply-chain detail default to operator/authority so they
     * never surface to consumers unless explicitly reclassified. Elevation is
     * only ever granted by a valid Laravel signed URL carrying a `tier` query
     * param in `order`; any missing/invalid signature or unknown tier fails
     * closed to `consumer` (see PublicationController::show()).
     */
    'tiers' => [
        'default' => 'consumer',
        'order'   => ['consumer', 'operator', 'authority'],
        'map'     => [
            'dpp_supply_chain_notes'        => 'operator',
            'dpp_manufacturing_site'        => 'operator',
            'dpp_declaration_of_conformity' => 'authority',
            'dpp_test_reports'              => 'authority',
            'dpp_certificates'              => 'authority',
        ],
    ],
];
