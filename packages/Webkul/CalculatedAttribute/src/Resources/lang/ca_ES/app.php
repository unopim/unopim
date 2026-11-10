<?php

return [

    'attribute' => [
        'calculated' => 'Calculat',
    ],

    'formula' => [
        'title'            => 'Fórmula',
        'label'            => 'Fórmula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => "El valor es deriva d'aquesta fórmula cada vegada que el producte es mostra, s'exporta o el retorna l'API. No s'emmagatzema mai i no es pot importar.",
        'reference-hint'   => 'Feu referència a altres atributs pel seu codi.',
        'available'        => 'Funcions disponibles',
        'examples'         => 'Exemples',
        'example-concat'   => 'Uneix dos atributs i omet els que són buits',
        'example-category' => 'El camí de les etiquetes de categoria, unit amb #',
        'example-fallback' => 'Recórrer a un segon atribut quan el primer és buit',
        'read-only'        => 'Calculat',
        'read-only-hint'   => "Aquest valor es calcula a partir d'altres atributs i no es pot editar.",

        'variables' => [
            'title'      => 'Variables disponibles',
            'sku'        => 'el SKU del producte',
            'type'       => 'el tipus de producte',
            'status'     => "l'estat del producte",
            'family'     => "el codi de la família d'atributs",
            'parent'     => 'el SKU del producte pare, per a variants',
            'categories' => 'els codis de les categories assignades',
            'channel'    => "el canal que s'està representant",
            'locale'     => "l'idioma que s'està representant",
        ],

        'errors' => [
            'syntax'              => "No s'ha pogut analitzar la fórmula: :detail",
            'unknown-variable'    => 'Atribut o variable «:name» desconegut a la fórmula.',
            'disallowed-function' => 'La funció «:name» no es permet en una fórmula.',
            'disallowed-operator' => "L'operador «:name» no es permet en una fórmula.",
            'reserved-variable'   => 'El nom «:name» està reservat i no es pot fer servir en una fórmula.',
            'too-long'            => 'La fórmula no pot superar els :max caràcters.',
            'empty'               => 'Un atribut calculat necessita una fórmula.',
            'circular'            => 'La fórmula crea una dependència circular: :cycle',
            'referenced-by'       => "Aquest atribut no es pot eliminar perquè l'utilitza la fórmula de: :attributes",
        ],
    ],

];
