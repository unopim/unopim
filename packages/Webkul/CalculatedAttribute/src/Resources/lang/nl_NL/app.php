<?php

return [

    'attribute' => [
        'calculated' => 'Berekend',
    ],

    'formula' => [
        'title'            => 'Formule',
        'label'            => 'Formule',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'De waarde wordt telkens opnieuw uit deze formule afgeleid wanneer het product wordt getoond, geëxporteerd of door de API wordt teruggegeven. Ze wordt nooit opgeslagen en kan niet worden geïmporteerd.',
        'reference-hint'   => 'Verwijs naar andere attributen met hun code.',
        'available'        => 'Beschikbare functies',
        'examples'         => 'Voorbeelden',
        'example-concat'   => 'Twee attributen samenvoegen en lege overslaan',
        'example-category' => 'Het pad met categorielabels, samengevoegd met #',
        'example-fallback' => 'Terugvallen op een tweede attribuut als het eerste leeg is',
        'read-only'        => 'Berekend',
        'read-only-hint'   => 'Deze waarde wordt berekend uit andere attributen en kan niet worden bewerkt.',

        'variables' => [
            'title'      => 'Beschikbare variabelen',
            'sku'        => 'de SKU van het product',
            'type'       => 'het producttype',
            'status'     => 'de productstatus',
            'family'     => 'de code van de attribuutfamilie',
            'parent'     => 'de SKU van het bovenliggende product, bij varianten',
            'categories' => 'de toegewezen categoriecodes',
            'channel'    => 'het kanaal dat wordt weergegeven',
            'locale'     => 'de taal die wordt weergegeven',
        ],

        'errors' => [
            'syntax'              => 'De formule kon niet worden gelezen: :detail',
            'unknown-variable'    => 'Onbekend attribuut of onbekende variabele ":name" in de formule.',
            'disallowed-function' => 'De functie ":name" is niet toegestaan in een formule.',
            'disallowed-operator' => 'De operator ":name" is niet toegestaan in een formule.',
            'reserved-variable'   => 'De naam ":name" is gereserveerd en mag niet in een formule worden gebruikt.',
            'too-long'            => 'De formule mag niet langer zijn dan :max tekens.',
            'empty'               => 'Een berekend attribuut heeft een formule nodig.',
            'circular'            => 'De formule veroorzaakt een circulaire afhankelijkheid: :cycle',
            'referenced-by'       => 'Dit attribuut kan niet worden verwijderd omdat het wordt gebruikt door de formule van: :attributes',
        ],
    ],

];
