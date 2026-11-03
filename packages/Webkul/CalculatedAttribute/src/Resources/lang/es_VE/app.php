<?php

return [

    'attribute' => [
        'calculated' => 'Calculado',
    ],

    'formula' => [
        'title'            => 'Fórmula',
        'label'            => 'Fórmula',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'El valor se deriva de esta fórmula cada vez que se muestra el producto, se exporta o lo devuelve la API. Nunca se almacena y no se puede importar.',
        'reference-hint'   => 'Haga referencia a otros atributos por su código.',
        'available'        => 'Funciones disponibles',
        'examples'         => 'Ejemplos',
        'example-concat'   => 'Unir dos atributos, omitiendo los que estén vacíos',
        'example-category' => 'La ruta de etiquetas de categoría, unida con #',
        'example-fallback' => 'Recurrir a un segundo atributo cuando el primero está vacío',
        'read-only'        => 'Calculado',
        'read-only-hint'   => 'Este valor se calcula a partir de otros atributos y no se puede editar.',

        'variables' => [
            'title'      => 'Variables disponibles',
            'sku'        => 'el SKU del producto',
            'type'       => 'el tipo de producto',
            'status'     => 'el estado del producto',
            'family'     => 'el código de la familia de atributos',
            'parent'     => 'el SKU del producto padre, para variantes',
            'categories' => 'los códigos de las categorías asignadas',
            'channel'    => 'el canal que se está representando',
            'locale'     => 'el idioma que se está representando',
        ],

        'errors' => [
            'syntax'              => 'No se ha podido analizar la fórmula: :detail',
            'unknown-variable'    => 'Atributo o variable ":name" desconocido en la fórmula.',
            'disallowed-function' => 'La función ":name" no está permitida en una fórmula.',
            'disallowed-operator' => 'El operador ":name" no está permitido en una fórmula.',
            'reserved-variable'   => 'El nombre ":name" está reservado y no puede usarse en una fórmula.',
            'too-long'            => 'La fórmula no puede superar los :max caracteres.',
            'empty'               => 'Un atributo calculado necesita una fórmula.',
            'circular'            => 'La fórmula crea una dependencia circular: :cycle',
            'referenced-by'       => 'Este atributo no se puede eliminar porque lo usa la fórmula de: :attributes',
        ],
    ],

];
