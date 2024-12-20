<?php

return [
    'users' => [
        'sessions' => [
            'email'                => 'Sähköpostiosoite',
            'forget-password-link' => 'Unohtuiko salasana?',
            'password'             => 'Salasana',
            'submit-btn'           => 'Kirjaudu sisään',
            'title'                => 'Kirjaudu sisään',
        ],

        'forget-password' => [
            'create' => [
                'email'                => 'Rekisteröity sähköposti',
                'email-not-exist'      => 'Sähköpostiosoitetta ei löydy',
                'page-title'           => 'Unohtunut salasana',
                'reset-link-sent'      => 'Salasanan palautuslinkki lähetetty',
                'email-settings-error' => 'Sähköpostia ei voitu lähettää. Tarkista sähköpostiasetukset',
                'sign-in-link'         => 'Takaisin kirjautumiseen?',
                'submit-btn'           => 'Palauta',
                'title'                => 'Palauta salasana',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => 'Takaisin kirjautumiseen?',
            'confirm-password' => 'Vahvista salasana',
            'email'            => 'Rekisteröity sähköposti',
            'password'         => 'Salasana',
            'submit-btn'       => 'Palauta salasana',
            'title'            => 'Palauta salasana',
        ],
    ],

    'notifications' => [
        'description-text' => 'Luettelo kaikista ilmoituksista',
        'marked-success'   => 'Ilmoitus merkitty onnistuneesti',
        'no-record'        => 'Tietueita ei löytynyt',
        'read-all'         => 'Merkitse luetuksi',
        'title'            => 'Ilmoitukset',
        'view-all'         => 'Näytä kaikki',
        'status'           => [
            'all'        => 'Kaikki',
            'canceled'   => 'Peruutettu',
            'closed'     => 'Suljettu',
            'completed'  => 'Valmis',
            'pending'    => 'Odottaa',
            'processing' => 'Käsitellään',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => 'Takaisin',
            'change-password'   => 'Vaihda salasana',
            'confirm-password'  => 'Vahvista salasana',
            'current-password'  => 'Nykyinen salasana',
            'email'             => 'Sähköposti',
            'general'           => 'Yleinen',
            'invalid-password'  => 'Nykyinen salasana on virheellinen.',
            'name'              => 'Nimi',
            'password'          => 'Salasana',
            'profile-image'     => 'Profiilikuva',
            'save-btn'          => 'Tallenna tili',
            'title'             => 'Oma tili',
            'ui-locale'         => 'Käyttöliittymän kieli',
            'update-success'    => 'Tili päivitetty onnistuneesti',
            'upload-image-info' => 'Lataa profiilikuva (110px X 110px)',
            'user-timezone'     => 'Aikavyöhyke',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => 'Kojelauta',
            'user-info'        => 'Seuraa nopeasti PIM:si tärkeimpiä asioita',
            'user-name'        => 'Hei! :user_name',
            'catalog-details'  => 'Luettelo',
            'total-families'   => 'Yhteensä perheitä',
            'total-attributes' => 'Yhteensä attribuutteja',
            'total-groups'     => 'Yhteensä ryhmiä',
            'total-categories' => 'Yhteensä kategorioita',
            'total-products'   => 'Yhteensä tuotteita',
            'settings-details' => 'Luettelon rakenne',
            'total-locales'    => 'Yhteensä kielialueita',
            'total-currencies' => 'Yhteensä valuuttoja',
            'total-channels'   => 'Yhteensä kanavia',
        ],
    ],

    'acl' => [
        'addresses'                => 'Osoitteet',
        'attribute-families'       => 'Attribuuttiperheet',
        'attribute-groups'         => 'Attribuuttiryhmät',
        'attributes'               => 'Attribuutit',
        'cancel'                   => 'Peruuta',
        'catalog'                  => 'Tuoteluettelo',
        'categories'               => 'Kategoriat',
        'channels'                 => 'Kanavat',
        'configure'                => 'Konfiguroi',
        'configuration'            => 'Konfiguraatio',
        'copy'                     => 'Kopioi',
        'create'                   => 'Luo',
        'currencies'               => 'Valuutat',
        'dashboard'                => 'Ohjauspaneeli',
        'data-transfer'            => 'Tietojen siirto',
        'delete'                   => 'Poista',
        'edit'                     => 'Muokkaa',
        'email-templates'          => 'Sähköpostimallit',
        'events'                   => 'Tapahtumat',
        'groups'                   => 'Ryhmät',
        'import'                   => 'Tuonti',
        'imports'                  => 'Tuonti',
        'invoices'                 => 'Laskut',
        'locales'                  => 'Paikalliset asetukset',
        'magic-ai'                 => 'Magic AI',
        'marketing'                => 'Markkinointi',
        'newsletter-subscriptions' => 'Uutiskirjeen tilaukset',
        'note'                     => 'Huomautus',
        'orders'                   => 'Tilaukset',
        'products'                 => 'Tuotteet',
        'promotions'               => 'Mainokset',
        'refunds'                  => 'Palautukset',
        'reporting'                => 'Raportointi',
        'reviews'                  => 'Arvostelut',
        'roles'                    => 'Roolit',
        'sales'                    => 'Myynti',
        'search-seo'               => 'Hakukoneoptimointi',
        'search-synonyms'          => 'Hakusynonyymit',
        'search-terms'             => 'Hakusanat',
        'settings'                 => 'Asetukset',
        'shipments'                => 'Toimitukset',
        'sitemaps'                 => 'Sivukartat',
        'subscribers'              => 'Uutiskirjeen tilaajat',
        'tax-categories'           => 'Veroluokat',
        'tax-rates'                => 'Verokannat',
        'taxes'                    => 'Verot',
        'themes'                   => 'Teemat',
        'integration'              => 'Integraatio',
        'url-rewrites'             => 'URL-uudelleenohjaukset',
        'users'                    => 'Käyttäjät',
        'category_fields'          => 'Kategoriakentät',
        'view'                     => 'Näytä',
        'execute'                  => 'Suorita työ',
        'history'                  => 'Historia',
        'restore'                  => 'Palauta',
        'integrations'             => 'Integraatiot',
        'api'                      => 'API',
        'tracker'                  => 'Työseuranta',
        'imports'                  => 'Tuonti',
        'exports'                  => 'Vienti',
    ],

    'errors' => [
        'dashboard' => 'Ohjauspaneeli',
        'go-back'   => 'Palaa takaisin',
        'support'   => 'Jos ongelma jatkuu, ota yhteyttä sähköpostitse <a href=":link" class=":class">:email</a>., jos ongelma jatkuu, ota yhteyttä sähköpostitse <a href=":link" class=":class">:email</a> avuksi.',

        '404' => [
            'description' => 'Voi ei! Sivua, jota etsit, ei ole saatavilla. Emme löytäneet etsimääsi.',
            'title'       => '404 Sivua ei löydy',
        ],

        '401' => [
            'description' => 'Voi ei! Näyttää siltä, että sinulla ei ole oikeuksia päästä tälle sivulle. Sinulta puuttuvat tarvittavat tunnukset.',
            'title'       => '401 Käyttöoikeudet puuttuvat',
            'message'     => 'Autentikointi epäonnistui, koska tunnukset olivat virheellisiä tai aikaraja on umpeutunut.',
        ],

        '403' => [
            'description' => 'Voi ei! Tämä sivu on rajoitettu. Sinulla ei ole tarvittavia oikeuksia nähdä tätä sisältöä.',
            'title'       => '403 Pääsy kielletty',
        ],

        '413' => [
            'description' => 'Voi ei! Näyttää siltä, että yrität ladata liian suuren tiedoston. Jos haluat ladata sen, päivitä PHP-konfiguraatio.',
            'title'       => '413 Sisältö liian suuri',
        ],

        '419' => [
            'description' => 'Voi ei! Istuntosi on päättynyt. Päivitä sivu ja kirjaudu uudelleen sisään jatkaaksesi.',
            'title'       => '419 Istunto on päättynyt',
        ],

        '500' => [
            'description' => 'Voi ei! Jotain meni pieleen. Meillä on vaikeuksia ladata etsimääsi sivua.',
            'title'       => '500 Sisäinen palvelinvirhe',
        ],

        '503' => [
            'description' => 'Voi ei! Näyttää siltä, että olemme väliaikaisesti poissa käytöstä huoltotoimenpiteiden vuoksi. Tarkista myöhemmin uudelleen.',
            'title'       => '503 Palvelu ei saatavilla',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => 'Lataa',
        'export'     => 'Nopea vienti',
        'no-records' => 'Ei mitään vietävää',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => 'Tämä slug on käytössä joko kategorioissa tai tuotteissa.',
        'slug-reserved'   => 'Tämä slug on varattu.',
        'invalid-locale'  => 'Kelvottomat paikkakunnat :locales',
    ],

    'footer' => [
        'copy-right' => 'Powered by <a href="https://unopim.com/" target="_blank">UnoPim</a>, A Community Project by <a href="https://webkul.com/" target="_blank">Webkul</a>',
    ],

    'emails' => [
        'dear'   => 'Dear :admin_name',
        'thanks' => 'If you need any kind of help please contact us at <a href=":link" style=":style">:email</a>.<br/>Thanks!',

        'admin' => [
            'forgot-password' => [
                'description'    => 'You are receiving this email because we received a password reset request for your account.',
                'greeting'       => 'Forgot Password!',
                'reset-password' => 'Reset Password',
                'subject'        => 'Reset Password Email',
            ],
        ],
    ],

    'common' => [
        'yes'     => 'Yes',
        'no'      => 'No',
        'true'    => 'True',
        'false'   => 'False',
        'enable'  => 'Enabled',
        'disable' => 'Disabled',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => 'Poista',
            'no-result-found'              => 'Ei tuloksia löytynyt',
            'save-btn'                     => 'Tallenna konfiguraatio',
            'save-message'                 => 'Konfiguraatio tallennettiin onnistuneesti',
            'search'                       => 'Hae',
            'title'                        => 'Konfiguraatio',

            'general' => [
                'info'  => '',
                'title' => 'Yleinen',

                'general' => [
                    'info'  => '',
                    'title' => 'Yleinen',
                ],

                'magic-ai' => [
                    'info'  => 'Aseta Magic AI -vaihtoehdot.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API-avain',
                        'enabled'        => 'Oletus',
                        'llm-api-domain' => 'LLM API -alue',
                        'organization'   => 'Organisaation tunnus',
                        'title'          => 'Yleiset asetukset',
                        'title-info'     => 'Paranna kokemustasi Magic AI:llä syöttämällä yksinoikeutettu API-avain ja määrittämällä relevantti organisaatio saumattoman integraation saavuttamiseksi. Ota hallinta OpenAI-tunnuksistasi ja mukauta asetukset tarpeidesi mukaan.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => 'Luo',
                'title'      => 'Integraatiot',

                'datagrid' => [
                    'delete'          => 'Poista',
                    'edit'            => 'Muokkaa',
                    'id'              => 'ID',
                    'name'            => 'Nimi',
                    'user'            => 'Käyttäjä',
                    'client-id'       => 'Asiakas-ID',
                    'permission-type' => 'Pääsypääntyyppi',
                ],
            ],

            'create' => [
                'access-control' => 'Pääsyoikeuden hallinta',
                'all'            => 'Kaikki',
                'back-btn'       => 'Takaisin',
                'custom'         => 'Mukautettu',
                'assign-user'    => 'Määritä käyttäjä',
                'general'        => 'Yleinen',
                'name'           => 'Nimi',
                'permissions'    => 'Oikeudet',
                'save-btn'       => 'Tallenna',
                'title'          => 'Uusi integraatio',
            ],

            'edit' => [
                'access-control' => 'Pääsyoikeuden hallinta',
                'all'            => 'Kaikki',
                'back-btn'       => 'Takaisin',
                'custom'         => 'Mukautettu',
                'assign-user'    => 'Määritä käyttäjä',
                'general'        => 'Yleinen',
                'name'           => 'Nimi',
                'credentials'    => 'Tunnistetiedot',
                'client-id'      => 'Asiakas-ID',
                'secret-key'     => 'Salainen avain',
                'generate-btn'   => 'Luo',
                're-secret-btn'  => 'Luo uudelleen salainen avain',
                'permissions'    => 'Oikeudet',
                'save-btn'       => 'Tallenna',
                'title'          => 'Muokkaa integraatiota',
            ],

            'being-used'                     => 'API-integraatio on jo käytössä admin-käyttäjällä',
            'create-success'                 => 'API-integraatio luotiin onnistuneesti',
            'delete-failed'                  => 'API-integraation poisto epäonnistui',
            'delete-success'                 => 'API-integraatio poistettiin onnistuneesti',
            'last-delete-error'              => 'Viimeistä API-integraatiota ei voi poistaa',
            'update-success'                 => 'API-integraatio päivitettiin onnistuneesti',
            'generate-key-success'           => 'API-avain luotiin onnistuneesti',
            're-generate-secret-key-success' => 'API-salainen avain luotiin uudelleen onnistuneesti',
            'client-not-found'               => 'Asiakasta ei löytynyt',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Tili',
                'app-version'   => 'Versio : :version',
                'logout'        => 'Kirjaudu ulos',
                'my-account'    => 'Oma Tili',
                'notifications' => 'Ilmoitukset',
                'visit-shop'    => 'Vierailu Kaupassa',
            ],

            'sidebar' => [
                'attribute-families'       => 'Attribuuttiperheet',
                'attribute-groups'         => 'Attribuuttiryhmät',
                'attributes'               => 'Attribuutit',
                'history'                  => 'Historia',
                'edit-section'             => 'Tiedot',
                'general'                  => 'Yleinen',
                'catalog'                  => 'Tuoteluettelo',
                'categories'               => 'Kategoriat',
                'category_fields'          => 'Kategoriakentät',
                'channels'                 => 'Kanavat',
                'collapse'                 => 'Pudota alas',
                'configure'                => 'Konfiguroi',
                'currencies'               => 'Valuutat',
                'dashboard'                => 'Ohjauspaneeli',
                'data-transfer'            => 'Tietojen siirto',
                'groups'                   => 'Ryhmät',
                'tracker'                  => 'Työn seurantapaneeli',
                'imports'                  => 'Tuo',
                'exports'                  => 'Vie',
                'locales'                  => 'Paikallisuudet',
                'magic-ai'                 => 'Magic AI',
                'mode'                     => 'Tumma tila',
                'products'                 => 'Tuotteet',
                'roles'                    => 'Roolit',
                'settings'                 => 'Asetukset',
                'themes'                   => 'Teemat',
                'users'                    => 'Käyttäjät',
                'integrations'             => 'Integraatiot',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'Mikään tallenne ei ole valittuna.',
                'must-select-a-mass-action-option' => 'Sinun täytyy valita massatoiminto.',
                'must-select-a-mass-action'        => 'Sinun täytyy valita massatoiminto.',
            ],

            'toolbar' => [
                'length-of' => ':length of',
                'of'        => 'of',
                'per-page'  => 'Per Page',
                'results'   => ':total Results',
                'selected'  => ':total Selected',

                'mass-actions' => [
                    'submit'        => 'Lähetä',
                    'select-option' => 'Valitse vaihtoehto',
                    'select-action' => 'Valitse toiminto',
                ],

                'filter' => [
                    'title' => 'Suodata',
                ],

                'search_by' => [
                    'code'       => 'Etsi koodilla',
                    'code_or_id' => 'Etsi koodilla tai id:llä',
                ],

                'search' => [
                    'title' => 'Etsi',
                ],
            ],

            'filters' => [
                'select'   => 'Valitse',
                'title'    => 'Sovita suodattimet',
                'save'     => 'Tallenna',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Kirjoita vähintään 2 merkkiä...',
                        'no-results'        => 'Ei tuloksia...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Tyhjennä kaikki',
                    'title'     => 'Mukautetut suodattimet',
                ],

                'boolean-options' => [
                    'false' => 'Epätosi',
                    'true'  => 'Tosi',
                ],

                'date-options' => [
                    'last-month'        => 'Viime kuukausi',
                    'last-six-months'   => 'Viimeiset 6 kuukautta',
                    'last-three-months' => 'Viimeiset 3 kuukautta',
                    'this-month'        => 'Tämä kuukausi',
                    'this-week'         => 'Tämä viikko',
                    'this-year'         => 'Tämä vuosi',
                    'today'             => 'Tänään',
                    'yesterday'         => 'Eilen',
                ],
            ],

            'table' => [
                'actions'              => 'Toiminnot',
                'no-records-available' => 'Ei tallenteita saatavilla.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Hyväksy',
                'disagree-btn' => 'Epäile',
                'message'      => 'Oletko varma, että haluat tehdä tämän toiminnon?',
                'title'        => 'Oletko varma?',
            ],

            'delete' => [
                'agree-btn'    => 'Poista',
                'disagree-btn' => 'Peruuta',
                'message'      => 'Oletko varma, että haluat poistaa?',
                'title'        => 'Vahvista poisto',
            ],

            'history' => [
                'title'           => 'Historialla esikatselu',
                'subtitle'        => 'Katsokaa nopeasti päivityksenne ja muutoksenne.',
                'close-btn'       => 'Sulje',
                'version-label'   => 'Versio',
                'date-time-label' => 'Päivämäärä/Aika',
                'user-label'      => 'Käyttäjä',
                'name-label'      => 'Avain',
                'old-value-label' => 'Vanha arvo',
                'new-value-label' => 'Uusi arvo',
                'no-history'      => 'Ei historiaa löytynyt',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Lisää valittu tuote',
                'empty-info'    => 'Ei tuotteita saatavilla haun mukaisella hakusanalla.',
                'empty-title'   => 'Tuotteita ei löytynyt',
                'product-image' => 'Tuotteen kuva',
                'qty'           => ':qty Saatavilla',
                'sku'           => 'SKU - :sku',
                'title'         => 'Valitse tuotteet',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Lisää kuva',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Luo kuva',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Vain kuva tiedostot (.jpeg, .jpg, .png, ..) ovat sallittuja.',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Käytä',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Luo',
                    'generating'       => 'Luodaan...',
                    'hd'               => 'HD',
                    'model'            => 'Malli',
                    'number-of-images' => 'Kuvien määrä',
                    'prompt'           => 'Ohje',
                    'quality'          => 'Laatu',
                    'regenerate'       => 'Luo uudelleen',
                    'regenerating'     => 'Luo uudelleen...',
                    'size'             => 'Koko',
                    'standard'         => 'Vakio',
                    'title'            => 'AI-kuvien luominen',
                ],

                'placeholders' => [
                    'front'     => 'Etupuoli',
                    'next'      => 'Seuraava',
                    'size'      => 'Koko',
                    'use-cases' => 'Käyttötapaukset',
                    'zoom'      => 'Zoom',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Lisää video',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Vain video tiedostot (.mp4, .mov, .ogg ..) ovat sallittuja.',
            ],

            'files' => [
                'add-file-btn'      => 'Lisää tiedosto',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'Vain pdf tiedostot ovat sallittuja',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                  => 'Käytä',
                'generate'               => 'Luo',
                'generated-content'      => 'Luo sisältöä',
                'generated-content-info' => 'AI-sisältö voi olla harhaanjohtavaa. Tarkista luotu sisältö ennen sen käyttöä.',
                'generating'             => 'Luodaan...',
                'prompt'                 => 'Ohje',
                'title'                  => 'AI-apu',
                'model'                  => 'Malli',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 Uncensored',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],
];
