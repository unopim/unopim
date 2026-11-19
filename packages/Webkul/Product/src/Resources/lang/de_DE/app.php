<?php

return [
    'acl' => [
        'variant-structures' => 'Variantenstrukturen',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'Kopie von - :value',
        'copy-of'                       => 'Kopie von :value',
        'variant-already-exist-message' => 'Variante mit denselben Attributoptionen existiert bereits.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'Produkte vom Typ :type können nicht kopiert werden',
    ],

    'type'     => [
        'configurable'  => 'Konfigurierbar',
        'simple'        => 'Einfach',
        'variant-group' => 'Variantengruppe',
        'variant_group' => 'Variantengruppe',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'Zirkulärer Vorlagenverweis erkannt: :path.',
            'currency-not-allowed'    => 'Nur Preisattribute akzeptieren eine Währung, aber ":attribute" ist kein Preisattribut.',
            'currency-not-enabled'    => 'Die in der Vorlage verwendete Währung ":currency" ist für keinen Kanal aktiviert.',
            'currency-not-in-channel' => 'Die Währung ":currency" ist für den Kanal ":channel" nicht aktiviert.',
            'filter-failed'           => 'Der Filter ":filter" konnte nicht auf den Wert von ":attribute" angewendet werden.',
            'filter-not-supported'    => 'Der Filter ":filter" kann nicht auf das Attribut ":attribute" angewendet werden.',
            'format-date-position'    => 'Der Filter format_date muss der erste Filter in ":expression" sein.',
            'inside-html-tag'         => 'Vorlagenausdrücke dürfen nicht innerhalb von HTML-Tags oder -Attributen wie Links und Bildern stehen.',
            'invalid-filter-argument' => 'Der Filter ":filter" in ":expression" hat ein fehlendes oder ungültiges Argument.',
            'invalid-reference'       => 'Die in ":attribute" gespeicherte Vorlage ist ungültig und konnte nicht gerendert werden.',
            'invalid-syntax'          => 'Der Vorlagenausdruck ":expression" ist ungültig. Verwenden Sie einen Attributcode, gefolgt von optionalen Filtern, zum Beispiel: name | upper.',
            'max-depth'               => 'Vorlagenverweise sind bei ":attribute" tiefer als :max Ebenen verschachtelt.',
            'missing-value'           => 'Das Attribut ":attribute" hat keinen Wert für diesen Kanal und dieses Gebietsschema.',
            'not-in-family'           => 'Das in der Vorlage verwendete Attribut ":attribute" gehört nicht zur Produktfamilie.',
            'output-too-large'        => 'Die gerenderte Vorlage ist größer als :max Bytes und wurde abgeschnitten.',
            'self-reference'          => 'Die Vorlage kann nicht auf ihr eigenes Attribut ":attribute" verweisen.',
            'single-brace'            => '":text" in einfachen geschweiften Klammern gefunden. Vorlagenausdrücke benötigen doppelte geschweifte Klammern.',
            'source-too-long'         => 'Eine Vorlage darf höchstens :max Bytes lang sein.',
            'too-complex'             => 'Die Vorlage ist zu komplex für die Prüfung. Kürzen oder vereinfachen Sie sie.',
            'too-many-expressions'    => 'Eine Vorlage darf höchstens :max Ausdrücke enthalten.',
            'too-many-filters'        => 'Der Vorlagenausdruck ":expression" darf höchstens :max Filter verwenden.',
            'unclosed-expression'     => 'Ein Vorlagenausdruck wurde geöffnet, aber nie geschlossen.',
            'unknown-attribute'       => 'Das in der Vorlage verwendete Attribut ":attribute" existiert nicht.',
            'unknown-currency'        => 'Die in der Vorlage verwendete Währung ":currency" existiert nicht.',
            'unknown-filter'          => 'Der Filter ":filter" wird nicht unterstützt. Verfügbare Filter: :filters.',
            'unsupported-attribute'   => 'Das Attribut ":attribute" kann nicht in einer Vorlage verwendet werden, da sein Typ nicht unterstützt wird.',
        ],
    ],
];
