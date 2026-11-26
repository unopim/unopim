<?php

return [
    'acl' => [
        'variant-structures' => 'Variantstructuren',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'kopie-van-:value',
        'copy-of'                       => 'Kopie van :value',
        'variant-already-exist-message' => 'Variant met dezelfde attribuutopties bestaat al.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Producten van type :type kunnen niet worden gekopieerd.',
    ],

    'type'     => [
        'configurable'  => 'Configureerbaar',
        'simple'        => 'Standaard',
        'variant-group' => 'Variantgroep',
        'variant_group' => 'Variantgroep',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Circulaire sjabloonverwijzing gedetecteerd: :path.',
            'currency-not-allowed'    => 'Alleen prijsattributen accepteren een valuta, maar ":attribute" is geen prijsattribuut.',
            'currency-not-enabled'    => 'De valuta ":currency" die in het sjabloon wordt gebruikt, is voor geen enkel kanaal ingeschakeld.',
            'currency-not-in-channel' => 'De valuta ":currency" is niet ingeschakeld voor het kanaal ":channel".',
            'filter-failed'           => 'Het filter ":filter" kon niet worden toegepast op de waarde van ":attribute".',
            'filter-not-supported'    => 'Het filter ":filter" kan niet worden toegepast op het attribuut ":attribute".',
            'format-date-position'    => 'Het filter format_date moet het eerste filter in ":expression" zijn.',
            'inside-html-tag'         => 'Sjabloonexpressies kunnen niet binnen HTML-tags of -attributen zoals links en afbeeldingen worden geplaatst.',
            'invalid-filter-argument' => 'Het filter ":filter" in ":expression" heeft een ontbrekend of ongeldig argument.',
            'invalid-reference'       => 'Het sjabloon dat is opgeslagen in ":attribute" is ongeldig en kon niet worden weergegeven.',
            'invalid-syntax'          => 'De sjabloonexpressie ":expression" is ongeldig. Gebruik een attribuutcode gevolgd door optionele filters, bijvoorbeeld: name | upper.',
            'max-depth'               => 'Sjabloonverwijzingen zijn bij ":attribute" meer dan :max niveaus diep genest.',
            'missing-value'           => 'Het attribuut ":attribute" heeft geen waarde voor dit kanaal en deze taal.',
            'not-in-family'           => 'Het attribuut ":attribute" dat in het sjabloon wordt gebruikt, behoort niet tot de productfamilie.',
            'output-too-large'        => 'Het gerenderde sjabloon is groter dan :max bytes en is afgekapt.',
            'self-reference'          => 'Het sjabloon kan niet verwijzen naar zijn eigen attribuut ":attribute".',
            'single-brace'            => '":text" gevonden tussen enkele accolades. Sjabloonexpressies vereisen dubbele accolades.',
            'source-too-long'         => 'Een sjabloon kan maximaal :max bytes lang zijn.',
            'too-complex'             => 'Het sjabloon is te complex om te controleren. Maak het korter of eenvoudiger.',
            'too-many-expressions'    => 'Een sjabloon kan maximaal :max expressies bevatten.',
            'too-many-filters'        => 'De sjabloonexpressie ":expression" kan maximaal :max filters gebruiken.',
            'unclosed-expression'     => 'Er is een sjabloonexpressie geopend maar nooit gesloten.',
            'unknown-attribute'       => 'Het attribuut ":attribute" dat in het sjabloon wordt gebruikt, bestaat niet.',
            'unknown-currency'        => 'De valuta ":currency" die in het sjabloon wordt gebruikt, bestaat niet.',
            'unknown-filter'          => 'Het filter ":filter" wordt niet ondersteund. Beschikbare filters: :filters.',
            'unsupported-attribute'   => 'Het attribuut ":attribute" kan niet in een sjabloon worden gebruikt omdat het type niet wordt ondersteund.',
        ],
    ],
];
