<?php

return [
    'type' => [
        'label' => 'Digitaal Productpaspoort',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Productpaspoort',
            'info'     => 'Publicatie-instellingen voor het digitale productpaspoort.',
            'settings' => [
                'title'                  => 'Productpaspoortinstellingen',
                'enabled'                => 'Ingeschakeld',
                'auto-publish'           => 'Automatisch publiceren bij opslaan',
                'completeness-threshold' => 'Volledigheidsdrempel (%)',
                'operator-name'          => 'Naam van de marktdeelnemer',
                'operator-address'       => 'Adres van de marktdeelnemer',
                'operator-eu-rep'        => 'Gemachtigde vertegenwoordiger in de EU',
                'support-url'            => 'Ondersteunings-URL',
            ],
        ],
    ],
];
