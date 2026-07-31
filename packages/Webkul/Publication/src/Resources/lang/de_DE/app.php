<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Veröffentlichung',
            'info'     => 'Öffentliche Bereitstellungsebene für veröffentlichte, sprachspezifische Inhalte.',
            'settings' => [
                'title'                            => 'Veröffentlichungseinstellungen',
                'enabled'                          => 'Aktiviert',
                'enabled-hint'                     => 'Hauptschalter für die öffentliche Bereitstellungsebene. Wenn deaktiviert, geben alle öffentlichen Pass-URLs 404 zurück. Die Pass-Ansichten im Admin bleiben unberührt — blenden Sie diese über die Einstellung Digitaler Produktpass aus.',
                'base-url'                         => 'Basis-URL',
                'base-url-hint'                    => 'Öffentliche Adresse, unter der die Pässe bereitgestellt werden; wird zur Erzeugung von QR-Codes und teilbaren Links verwendet. Leer lassen, um die eigene Domain dieser Website zu verwenden.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl'                        => 'Cache-TTL (Sekunden)',
                'cache-ttl-hint'                   => 'Wie lange ein gemeinsamer Cache (CDN oder Reverse Proxy) einen gerenderten öffentlichen Pass wiederverwenden darf. Browser validieren bei jedem Besuch neu, sodass Änderungen, Rücknahmen und das Abschalten der Ebene sofort wirken.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit'                       => 'Ratenbegrenzung (Anfragen/Minute)',
                'rate-limit-hint'                  => 'Maximale Anzahl öffentlicher Pass-Anfragen pro Minute von einem einzelnen Besucher, bevor dieser gedrosselt wird.',
                'rate-limit-placeholder'           => '60',
                'indexable'                        => 'Indexierung durch Suchmaschinen zulassen',
                'indexable-hint'                   => 'Suchmaschinen das Indexieren öffentlicher Pass-Seiten erlauben. Deaktivieren, damit Pässe über den Link erreichbar bleiben, aber in Suchergebnissen verborgen sind.',
                'gs1-passport-channel'             => 'GS1-Digital-Link-Passkanal',
                'gs1-passport-channel-hint'        => 'Der Kanal, auf den ein gescannter GS1-Barcode (/01/{gtin}) verweist, wenn ein Produkt auf mehreren Kanälen veröffentlicht ist. Leer lassen, um den ersten aktivierten Kanal zu verwenden.',
                'gs1-passport-channel-placeholder' => 'Erster aktivierter Kanal (automatisch)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Entwurf',
            'published' => 'Veröffentlicht',
            'withdrawn' => 'Zurückgezogen',
            'redacted'  => 'Geschwärzt',
        ],
        'product-delete-blocked' => 'Dieses Produkt kann nicht gelöscht werden, solange veröffentlichte Pässe vorhanden sind. Ziehen Sie diese zuerst zurück.',
        'channel-delete-blocked' => 'Dieser Kanal kann nicht gelöscht werden, solange veröffentlichte Pässe vorhanden sind. Ziehen Sie diese zuerst zurück.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Produktpass nicht gefunden.',
            'notice'  => 'Dieser Produktpass ist nicht verfügbar. Er ist möglicherweise noch nicht veröffentlicht oder der Link ist falsch.',
        ],
        '429' => [
            'heading' => 'Zu viele Anfragen.',
            'notice'  => 'Sie haben zu viele Anfragen gestellt. Bitte warten Sie einen Moment und versuchen Sie es erneut.',
        ],
        'withdrawn' => [
            'heading' => 'Dieser Produktpass ist nicht mehr verfügbar.',
            'notice'  => 'Dieser Datensatz wird aus Gründen der Transparenz aufbewahrt, wird jedoch nicht mehr aktiv gepflegt.',
        ],
    ],
];
