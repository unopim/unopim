<?php

use Webkul\ProductPassport\Services\PassportPayloadBuilder;

return [
    'types' => [
        'dpp' => [
            'label'           => 'passport::app.type.label',
            'payload_builder' => PassportPayloadBuilder::class,
            'template'        => 'passport::public.passport',
            'required_group'  => 'dpp',
            'route_prefix'    => 'p',
        ],
    ],
];
