<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Vollständigkeit',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Vollständigkeit erfolgreich aktualisiert',
                    'title'               => 'Vollständigkeit',
                    'configure'           => 'Vollständigkeit konfigurieren',
                    'channel-required'    => 'In Kanälen erforderlich',
                    'save-btn'            => 'Speichern',
                    'back-btn'            => 'Zurück',
                    'mass-update-success' => 'Vollständigkeit erfolgreich aktualisiert',
                    'datagrid'            => [
                        'code'             => 'Code',
                        'name'             => 'Name',
                        'channel-required' => 'In Kanälen erforderlich',
                        'actions'          => [
                            'change-requirement' => 'Vollständigkeitsanforderung ändern',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Vollständig',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Vollständigkeit',
                    'subtitle' => 'Durchschnittliche Vollständigkeit',
                ],
                'required-attributes' => 'fehlende Pflichtattribute',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Vollständigkeitsberechnung abgeschlossen',
        'completeness-calculated'        => 'Vollständigkeit für :count Produkte berechnet.',
        'completeness-calculated-family' => 'Vollständigkeit für :count Produkte in der Familie ":family" berechnet.',
        'email-subject'                  => 'Vollständigkeitsberechnung abgeschlossen',
        'email-greeting'                 => 'Hallo,',
        'email-body'                     => 'Die Vollständigkeitsberechnung wurde für :count Produkte abgeschlossen.',
        'email-body-family'              => 'Die Vollständigkeitsberechnung wurde für :count Produkte in der Attributfamilie ":family" abgeschlossen.',
        'email-footer'                   => 'Sie können die Vollständigkeitsdetails auf Ihrem Dashboard einsehen.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Berechnete Produkte',
                'suggestion'          => [
                    'low'     => 'Niedrige Vollständigkeit, fügen Sie Details hinzu, um sie zu verbessern.',
                    'medium'  => 'Weiter so, fügen Sie weiter Informationen hinzu.',
                    'high'    => 'Fast vollständig, nur noch wenige Details fehlen.',
                    'perfect' => 'Produktinformationen sind vollständig.',
                ],
            ],
        ],
    ],
];
