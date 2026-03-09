<?php

return [

    'attribute' => [
        'measurement' => 'Misurazione',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Crea famiglia di misurazione',
            'code'     => 'Codice',
            'standard' => 'Codice unità standard',
            'symbol'   => 'Simbolo',
            'save'     => 'Salva',
        ],

        'edit' => [
            'measurement_edit' => 'Modifica famiglia di misurazione',
            'back'             => 'Indietro',
            'save'             => 'Salva',
            'general'          => 'Generale',
            'code'             => 'Codice',
            'label'            => 'Etichetta',
            'units'            => 'Unità',
            'create_units'     => 'Crea unità',
        ],

        'unit' => [
            'edit_unit'   => 'Modifica unità',
            'create_unit' => 'Crea unità',
            'symbol'      => 'Simbolo',
            'save'        => 'Salva',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Famiglie di misurazione',
        'measurement_family'   => 'Famiglia di misurazione',
        'measurement_unit'     => 'Unità di misura',
    ],

    'datagrid' => [
        'labels'        => 'Etichette',
        'code'          => 'Codice',
        'standard_unit' => 'Unità standard',
        'unit_count'    => 'Numero di unità',
        'is_standard'   => 'Contrassegna come unità standard',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Famiglia di misurazione aggiornata con successo.',
            'deleted'      => 'Famiglia di misurazione eliminata con successo.',
            'mass_deleted' => 'Le famiglie di misurazione selezionate sono state eliminate con successo.',
        ],

        'unit' => [
            'not_found'         => 'Famiglia di misurazione non trovata.',
            'already_exists'    => 'Il codice dell’unità esiste già.',
            'not_foundd'        => 'Unità non trovata.',
            'deleted'           => 'Unità eliminata con successo.',
            'no_items_selected' => 'Nessun elemento selezionato.',
            'mass_deleted'      => 'Le unità di misura selezionate sono state eliminate con successo.',
        ],
    ],

];
