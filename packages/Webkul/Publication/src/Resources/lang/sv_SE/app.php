<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'Publicering',
            'info'     => 'Offentligt serveringslager för publicerat, språkspecifikt innehåll.',
            'settings' => [
                'title'                            => 'Publiceringsinställningar',
                'enabled'                          => 'Aktiverad',
                'base-url'                         => 'Bas-URL',
                'cache-ttl'                        => 'Cache-TTL (sekunder)',
                'rate-limit'                       => 'Hastighetsgräns (förfrågningar/minut)',
                'indexable'                        => 'Tillåt indexering av sökmotorer',
                'enabled-hint'                     => 'Huvudbrytare för den publika visningsnivån. När den är av returnerar varje publik pass-URL 404. Passvyerna i administrationen påverkas inte — dölj dem med inställningen Digitalt produktpass.',
                'base-url-hint'                    => 'Publik adress där passen visas, används för att skapa QR-koder och delbara länkar. Lämna tomt för att använda webbplatsens egen domän.',
                'base-url-placeholder'             => 'https://dpp.example.com',
                'cache-ttl-hint'                   => 'Hur länge en delad cache (CDN eller omvänd proxy) får återanvända ett renderat publikt pass. Webbläsare omvaliderar vid varje besök, så ändringar, återkallanden och att stänga av nivån får effekt direkt.',
                'cache-ttl-placeholder'            => '3600',
                'rate-limit-hint'                  => 'Högsta antal publika pass-förfrågningar per minut från en enskild besökare innan de begränsas.',
                'rate-limit-placeholder'           => '60',
                'indexable-hint'                   => 'Låt sökmotorer indexera publika passidor. Stäng av för att hålla passen nåbara via länk men dolda i sökresultat.',
                'gs1-passport-channel'             => 'GS1 Digital Link-passkanal',
                'gs1-passport-channel-hint'        => 'Kanalen som en skannad GS1-streckkod (/01/{gtin}) leder till när en produkt publiceras på flera kanaler. Lämna tomt för att använda den första aktiverade kanalen.',
                'gs1-passport-channel-placeholder' => 'Första aktiverade kanalen (automatiskt)',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'Utkast',
            'published' => 'Publicerad',
            'withdrawn' => 'Indragen',
            'redacted'  => 'Redigerad (dold)',
        ],
        'product-delete-blocked' => 'Denna produkt kan inte tas bort så länge den har publicerade pass. Dra tillbaka dem först.',
        'channel-delete-blocked' => 'Denna kanal kan inte tas bort så länge den har publicerade pass. Dra tillbaka dem först.',
    ],

    'public' => [
        '404' => [
            'heading' => 'Passet hittades inte.',
            'notice'  => 'Detta produktpass är inte tillgängligt. Det kanske inte har publicerats ännu, eller så är länken felaktig.',
        ],
        '429' => [
            'heading' => 'För många förfrågningar. Försök igen om en stund.',
            'notice'  => 'Du har gjort för många förfrågningar. Vänta en stund och försök igen.',
        ],
        'withdrawn' => [
            'heading' => 'Detta pass är inte längre tillgängligt.',
            'notice'  => 'Denna post behålls av öppenhetsskäl men underhålls inte längre aktivt.',
        ],
        'release' => [
            'banner'       => 'Release :sequence of this passport',
            'superseded'   => 'This is an earlier state of the passport, kept exactly as it was published. A newer state exists.',
            'current'      => 'This is the current state of the passport.',
            'view-current' => 'View the current passport',
        ],
    ],
];
