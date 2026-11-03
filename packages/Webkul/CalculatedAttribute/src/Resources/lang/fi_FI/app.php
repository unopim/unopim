<?php

return [

    'attribute' => [
        'calculated' => 'Laskettu',
    ],

    'formula' => [
        'title'            => 'Kaava',
        'label'            => 'Kaava',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => 'Arvo johdetaan tästä kaavasta joka kerta, kun tuote näytetään, viedään tai palautetaan rajapinnasta. Sitä ei koskaan tallenneta eikä sitä voi tuoda.',
        'reference-hint'   => 'Viittaa muihin attribuutteihin niiden koodilla.',
        'available'        => 'Käytettävissä olevat funktiot',
        'examples'         => 'Esimerkkejä',
        'example-concat'   => 'Yhdistä kaksi attribuuttia ja ohita tyhjät',
        'example-category' => 'Kategorianimien polku yhdistettynä merkillä #',
        'example-fallback' => 'Käytä toista attribuuttia, kun ensimmäinen on tyhjä',
        'read-only'        => 'Laskettu',
        'read-only-hint'   => 'Tämä arvo lasketaan muista attribuuteista, eikä sitä voi muokata.',

        'variables' => [
            'title'      => 'Käytettävissä olevat muuttujat',
            'sku'        => 'tuotteen tuotekoodi',
            'type'       => 'tuotetyyppi',
            'status'     => 'tuotteen tila',
            'family'     => 'attribuuttiperheen koodi',
            'parent'     => 'ylätuotteen tuotekoodi, varianteille',
            'categories' => 'liitettyjen kategorioiden koodit',
            'channel'    => 'esitettävä kanava',
            'locale'     => 'esitettävä kieli',
        ],

        'errors' => [
            'syntax'              => 'Kaavaa ei voitu jäsentää: :detail',
            'unknown-variable'    => 'Tuntematon attribuutti tai muuttuja ":name" kaavassa.',
            'disallowed-function' => 'Funktio ":name" ei ole sallittu kaavassa.',
            'disallowed-operator' => 'Operaattori ":name" ei ole sallittu kaavassa.',
            'reserved-variable'   => 'Nimi ":name" on varattu, eikä sitä voi käyttää kaavassa.',
            'too-long'            => 'Kaava saa olla enintään :max merkkiä pitkä.',
            'empty'               => 'Laskettu attribuutti tarvitsee kaavan.',
            'circular'            => 'Kaava muodostaa kehäriippuvuuden: :cycle',
            'referenced-by'       => 'Tätä attribuuttia ei voi poistaa, koska sitä käyttää seuraavien kaava: :attributes',
        ],
    ],

];
