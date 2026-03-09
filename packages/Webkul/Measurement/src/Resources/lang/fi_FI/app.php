<?php

return [

    'attribute' => [
        'measurement' => 'Mittaus',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Luo mittausperhe',
            'code'     => 'Koodi',
            'standard' => 'Vakioyksikön koodi',
            'symbol'   => 'Symboli',
            'save'     => 'Tallenna',
        ],

        'edit' => [
            'measurement_edit' => 'Muokkaa mittausperhettä',
            'back'             => 'Takaisin',
            'save'             => 'Tallenna',
            'general'          => 'Yleinen',
            'code'             => 'Koodi',
            'label'            => 'Nimike',
            'units'            => 'Yksiköt',
            'create_units'     => 'Luo yksiköt',
        ],

        'unit' => [
            'edit_unit'   => 'Muokkaa yksikköä',
            'create_unit' => 'Luo yksikkö',
            'symbol'      => 'Symboli',
            'save'        => 'Tallenna',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Mittausperheet',
        'measurement_family'   => 'Mittausperhe',
        'measurement_unit'     => 'Mittayksikkö',
    ],

    'datagrid' => [
        'labels'        => 'Nimikkeet',
        'code'          => 'Koodi',
        'standard_unit' => 'Vakioyksikkö',
        'unit_count'    => 'Yksiköiden määrä',
        'is_standard'   => 'Merkitse vakioyksiköksi',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'Mittausperhe päivitettiin onnistuneesti.',
            'deleted'      => 'Mittausperhe poistettiin onnistuneesti.',
            'mass_deleted' => 'Valitut mittausperheet poistettiin onnistuneesti.',
        ],

        'unit' => [
            'not_found'         => 'Mittausperhettä ei löytynyt.',
            'already_exists'    => 'Yksikkökoodi on jo olemassa.',
            'not_foundd'        => 'Yksikköä ei löytynyt.',
            'deleted'           => 'Yksikkö poistettiin onnistuneesti.',
            'no_items_selected' => 'Kohteita ei ole valittu.',
            'mass_deleted'      => 'Valitut mittayksiköt poistettiin onnistuneesti.',
        ],
    ],

];
