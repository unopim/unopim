<?php

return [
    'users' => [
        'sessions' => [
            'email'                => 'E-postadress',
            'forget-password-link' => 'Glömt lösenord?',
            'password'             => 'Lösenord',
            'submit-btn'           => 'Logga in',
            'title'                => 'Logga in',
        ],

        'forget-password' => [
            'create' => [
                'email'                => 'Registrerad e-post',
                'email-not-exist'      => 'E-postadress finns inte',
                'page-title'           => 'Glömt lösenord',
                'reset-link-sent'      => 'Länk för att återställa lösenord skickad',
                'email-settings-error' => 'E-post kunde inte skickas. Kontrollera din e-postkonfiguration',
                'sign-in-link'         => 'Tillbaka till inloggning?',
                'submit-btn'           => 'Återställ',
                'title'                => 'Återställ lösenord',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => 'Tillbaka till inloggning?',
            'confirm-password' => 'Bekräfta lösenord',
            'email'            => 'Registrerad e-post',
            'password'         => 'Lösenord',
            'submit-btn'       => 'Återställ lösenord',
            'title'            => 'Återställ lösenord',
        ],
    ],

    'notifications' => [
        'description-text' => 'Lista över alla meddelanden',
        'marked-success'   => 'Meddelande markerat som läst',
        'no-record'        => 'Inget fyndat',
        'read-all'         => 'Markera alla som lästa',
        'title'            => 'Meddelanden',
        'view-all'         => 'Visa alla',
        'status'           => [
            'all'        => 'Alla',
            'canceled'   => 'Avbruten',
            'closed'     => 'Stängd',
            'completed'  => 'Slutförd',
            'pending'    => 'Väntar',
            'processing' => 'Bearbetning',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => 'Tillbaka',
            'change-password'   => 'Ändra lösenord',
            'confirm-password'  => 'Bekräfta lösenord',
            'current-password'  => 'Aktuellt lösenord',
            'email'             => 'E-postadress',
            'general'           => 'Allmänt',
            'invalid-password'  => 'Det aktuella lösenordet är felaktigt.',
            'name'              => 'Namn',
            'password'          => 'Lösenord',
            'profile-image'     => 'Profilbild',
            'save-btn'          => 'Spara konto',
            'title'             => 'Mitt konto',
            'ui-locale'         => 'UI-lokal',
            'update-success'    => 'Kontot uppdaterat framgångsrikt',
            'upload-image-info' => 'Ladda upp en profilbild (110px X 110px)',
            'user-timezone'     => 'Tidszon',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => 'Instrumentbräda',
            'user-info'        => 'Snabbövervakning, vad är viktat i ditt PIM',
            'user-name'        => 'Hej! :user_name',
            'catalog-details'  => 'Katalog',
            'total-families'   => 'Totala familjer',
            'total-attributes' => 'Totala attribut',
            'total-groups'     => 'Totala grupper',
            'total-categories' => 'Totala kategorier',
            'total-products'   => 'Totala produkter',
            'settings-details' => 'Katalogstruktur',
            'total-locales'    => 'Totala lokaler',
            'total-currencies' => 'Totala valutor',
            'total-channels'   => 'Totala kanaler',
        ],
    ],

    'acl' => [
        'addresses'                => 'Adresser',
        'attribute-families'       => 'Attributfamiljer',
        'attribute-groups'         => 'Attributgrupper',
        'attributes'               => 'Attribut',
        'cancel'                   => 'Avbryt',
        'catalog'                  => 'Katalog',
        'categories'               => 'Kategorier',
        'channels'                 => 'Kanaler',
        'configure'                => 'Konfigurera',
        'configuration'            => 'Konfiguration',
        'copy'                     => 'Kopiera',
        'create'                   => 'Skapa',
        'currencies'               => 'Valutor',
        'dashboard'                => 'Instrumentpanel',
        'data-transfer'            => 'Dataöverföring',
        'delete'                   => 'Radera',
        'edit'                     => 'Redigera',
        'email-templates'          => 'E-postmallar',
        'events'                   => 'Händelser',
        'groups'                   => 'Grupper',
        'import'                   => 'Importera',
        'imports'                  => 'Importer',
        'invoices'                 => 'Fakturor',
        'locales'                  => 'Lokaler',
        'magic-ai'                 => 'Magisk AI',
        'marketing'                => 'Marknadsföring',
        'newsletter-subscriptions' => 'Nyhetsbrevsprenumerationer',
        'note'                     => 'Anteckning',
        'orders'                   => 'Beställningar',
        'products'                 => 'Produkter',
        'promotions'               => 'Erbjudanden',
        'refunds'                  => 'Återbetalningar',
        'reporting'                => 'Rapportering',
        'reviews'                  => 'Recensioner',
        'roles'                    => 'Roller',
        'sales'                    => 'Försäljning',
        'search-seo'               => 'Sök & SEO',
        'search-synonyms'          => 'Söktermer & synonymer',
        'search-terms'             => 'Söktermer',
        'settings'                 => 'Inställningar',
        'shipments'                => 'Frakt',
        'sitemaps'                 => 'Sitemaps',
        'subscribers'              => 'Prenumeranter',
        'tax-categories'           => 'Skattekategorier',
        'tax-rates'                => 'Skattesatser',
        'taxes'                    => 'Skatter',
        'themes'                   => 'Teman',
        'integration'              => 'Integration',
        'url-rewrites'             => 'URL-omskrivningar',
        'users'                    => 'Användare',
        'category_fields'          => 'Kategori-fält',
        'view'                     => 'Visa',
        'execute'                  => 'Utföra',
        'history'                  => 'Historik',
        'restore'                  => 'Återställ',
        'integrations'             => 'Integrationer',
        'api'                      => 'API',
        'tracker'                  => 'Uppgiftsspårning',
        'imports'                  => 'Importer',
        'exports'                  => 'Exporter',
    ],

    'errors' => [
        'dashboard' => 'Instrumentpanel',
        'go-back'   => 'Tillbaka',
        'support'   => 'Om problemet kvarstår, vänligen kontakta oss via <a href=":link" class=":class">:email</a> för hjälp.',

        '404' => [
            'description' => 'Oops! Sidan du söker är på semester. Det verkar som att vi inte kan hitta vad du söker.',
            'title'       => '404 Sidan inte hittad',
        ],

        '401' => [
            'description' => 'Oops! Det verkar som att du inte har rätt behörigheter för att komma åt denna sida. Det verkar som att du saknar de nödvändiga behörigheterna.',
            'title'       => '401 Otillåten',
            'message'     => 'Autentiseringen misslyckades på grund av ogiltiga behörigheter eller utgången token.',
        ],

        '403' => [
            'description' => 'Oops! Denna sida är begränsad. Det verkar som att du inte har behörighet att se detta innehåll.',
            'title'       => '403 Förbjuden',
        ],

        '413' => [
            'description' => 'Oops! Det verkar som att du försöker ladda upp en för stor fil. Om du vill ladda upp den, vänligen uppdatera PHP-konfigurationen.',
            'title'       => '413 Innehåll för stort',
        ],

        '419' => [
            'description' => 'Oops! Din session har löpt ut. Vänligen uppdatera sidan och logga in igen för att fortsätta.',
            'title'       => '419 Sessionen har löpt ut',
        ],

        '500' => [
            'description' => 'Oops! Något gick fel. Det verkar som att vi har problem med att ladda den sida du söker.',
            'title'       => '500 Intern serverfel',
        ],

        '503' => [
            'description' => 'Oops! Det verkar som att vi är tillfälligt otillgängliga för underhåll. Kontrollera igen snart.',
            'title'       => '503 Tjänsten inte tillgänglig',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => 'Ladda ner',
        'export'     => 'Snabb export',
        'no-records' => 'Inga poster att exportera',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => 'Denna slug används i antingen kategorier eller produkter.',
        'slug-reserved'   => 'Denna slug är reserverad.',
        'invalid-locale'  => 'Ogiltiga lokaler :locales',
    ],

    'footer' => [
        'copy-right' => 'Driftad av <a href="https://unopim.com/" target="_blank">UnoPim</a>, Ett community-projekt från <a href="https://webkul.com/" target="_blank">Webkul</a>',
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
        'yes'     => 'Ja',
        'no'      => 'Nej',
        'true'    => 'Sann',
        'false'   => 'Falsk',
        'enable'  => 'Aktiverad',
        'disable' => 'Inaktiverad',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => 'Radera',
            'no-result-found'              => 'Inget resultat hittades',
            'save-btn'                     => 'Spara Konfiguration',
            'save-message'                 => 'Konfigurationen sparades framgångsrikt',
            'search'                       => 'Sök',
            'title'                        => 'Konfiguration',

            'general' => [
                'info'  => '',
                'title' => 'Allmänt',

                'general' => [
                    'info'  => '',
                    'title' => 'Allmänt',
                ],

                'magic-ai' => [
                    'info'  => 'Ställ in Magic AI alternativ.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API-nyckel',
                        'enabled'        => 'Aktiverad',
                        'llm-api-domain' => 'LLM API-domän',
                        'organization'   => 'Organisations-ID',
                        'title'          => 'Allmänna inställningar',
                        'title-info'     => 'Förbättra din upplevelse med Magic AI genom att ange din exklusiva API-nyckel och indikera den relevanta organisationen för smidig integration. Ta kontroll över dina OpenAI-uppgifter och anpassa inställningarna enligt dina specifika behov.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => 'Skapa',
                'title'      => 'Integrationer',

                'datagrid' => [
                    'delete'          => 'Radera',
                    'edit'            => 'Redigera',
                    'id'              => 'ID',
                    'name'            => 'Namn',
                    'user'            => 'Användare',
                    'client-id'       => 'Klient-ID',
                    'permission-type' => 'Tillståndstyp',
                ],
            ],

            'create' => [
                'access-control' => 'Åtkomstkontroll',
                'all'            => 'Alla',
                'back-btn'       => 'Tillbaka',
                'custom'         => 'Egen',
                'assign-user'    => 'Tilldela Användare',
                'general'        => 'Allmänt',
                'name'           => 'Namn',
                'permissions'    => 'Tillstånd',
                'save-btn'       => 'Spara',
                'title'          => 'Ny Integration',
            ],

            'edit' => [
                'access-control' => 'Åtkomstkontroll',
                'all'            => 'Alla',
                'back-btn'       => 'Tillbaka',
                'custom'         => 'Egen',
                'assign-user'    => 'Tilldela Användare',
                'general'        => 'Allmänt',
                'name'           => 'Namn',
                'credentials'    => 'Kredentialer',
                'client-id'      => 'Klient-ID',
                'secret-key'     => 'Sekret Nyckel',
                'generate-btn'   => 'Generera',
                're-secret-btn'  => 'Generera Om Sekret Nyckel',
                'permissions'    => 'Tillstånd',
                'save-btn'       => 'Spara',
                'title'          => 'Redigera Integration',
            ],

            'being-used'                     => 'API-integration används redan av Admin-användaren',
            'create-success'                 => 'API-integration skapades framgångsrikt',
            'delete-failed'                  => 'API-integration kunde inte raderas',
            'delete-success'                 => 'API-integration raderades framgångsrikt',
            'last-delete-error'              => 'Sista API-integration kan inte raderas',
            'update-success'                 => 'API-integration uppdaterades framgångsrikt',
            'generate-key-success'           => 'API-nyckel genererades framgångsrikt',
            're-generate-secret-key-success' => 'API-sekret-nyckel genererades om framgångsrikt',
            'client-not-found'               => 'Klient hittades inte',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Konto',
                'app-version'   => 'Version : :version',
                'logout'        => 'Logga ut',
                'my-account'    => 'Mitt Konto',
                'notifications' => 'Meddelanden',
                'visit-shop'    => 'Besök Butik',
            ],

            'sidebar' => [
                'attribute-families'       => 'Attributfamiljer',
                'attribute-groups'         => 'Attributgrupper',
                'attributes'               => 'Attribut',
                'history'                  => 'Historik',
                'edit-section'             => 'Data',
                'general'                  => 'Allmänt',
                'catalog'                  => 'Katalog',
                'categories'               => 'Kategorier',
                'category_fields'          => 'Kategorifält',
                'channels'                 => 'Kanaler',
                'collapse'                 => 'Kollapsa',
                'configure'                => 'Konfiguration',
                'currencies'               => 'Valutor',
                'dashboard'                => 'Instrumentpanel',
                'data-transfer'            => 'Dataöverföring',
                'groups'                   => 'Grupper',
                'tracker'                  => 'Jobb Tracker',
                'imports'                  => 'Importer',
                'exports'                  => 'Exporter',
                'locales'                  => 'Lokaliseringsinställningar',
                'magic-ai'                 => 'Magic AI',
                'mode'                     => 'Mörkt Läge',
                'products'                 => 'Produkter',
                'roles'                    => 'Roller',
                'settings'                 => 'Inställningar',
                'themes'                   => 'Teman',
                'users'                    => 'Användare',
                'integrations'             => 'Integrationer',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'Inga poster har valts.',
                'must-select-a-mass-action-option' => 'Du måste välja ett massaktionsval.',
                'must-select-a-mass-action'        => 'Du måste välja en massaktion.',
            ],

            'toolbar' => [
                'length-of' => ':length av',
                'of'        => 'av',
                'per-page'  => 'Per Sida',
                'results'   => ':total Resultat',
                'selected'  => ':total Valda',

                'mass-actions' => [
                    'submit'        => 'Skicka',
                    'select-option' => 'Välj Alternativ',
                    'select-action' => 'Välj Aktion',
                ],

                'filter' => [
                    'title' => 'Filter',
                ],

                'search_by' => [
                    'code'       => 'Sök efter kod',
                    'code_or_id' => 'Sök efter kod eller ID',
                ],

                'search' => [
                    'title' => 'Sök',
                ],
            ],

            'filters' => [
                'select'   => 'Välj',
                'title'    => 'Använd Filter',
                'save'     => 'Spara',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Skriv minst 2 tecken...',
                        'no-results'        => 'Inga resultat funna...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Rensa Alla',
                    'title'     => 'Anpassade Filter',
                ],

                'boolean-options' => [
                    'false' => 'Falskt',
                    'true'  => 'Sann',
                ],

                'date-options' => [
                    'last-month'        => 'Sista Månaden',
                    'last-six-months'   => 'Sista 6 Månaderna',
                    'last-three-months' => 'Sista 3 Månaderna',
                    'this-month'        => 'Denna Månaden',
                    'this-week'         => 'Denna Vecka',
                    'this-year'         => 'Detta Året',
                    'today'             => 'Idag',
                    'yesterday'         => 'Igår',
                ],
            ],

            'table' => [
                'actions'              => 'Åtgärder',
                'no-records-available' => 'Inga poster tillgängliga.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Håller med',
                'disagree-btn' => 'Oenig',
                'message'      => 'Är du säker på att du vill utföra denna åtgärd?',
                'title'        => 'Är du säker?',
            ],

            'delete' => [
                'agree-btn'    => 'Radera',
                'disagree-btn' => 'Avbryt',
                'message'      => 'Är du säker på att du vill radera?',
                'title'        => 'Bekräfta Radering',
            ],

            'history' => [
                'title'           => 'Historik Förhandsgranskning',
                'subtitle'        => 'Granska snabbt dina uppdateringar och ändringar.',
                'close-btn'       => 'Stäng',
                'version-label'   => 'Version',
                'date-time-label' => 'Datum/Tid',
                'user-label'      => 'Användare',
                'name-label'      => 'Nyckel',
                'old-value-label' => 'Gammal Värde',
                'new-value-label' => 'Ny Värde',
                'no-history'      => 'Ingen historik funnen',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Lägg Till Vald Produkt',
                'empty-info'    => 'Inga produkter tillgängliga för sökterm.',
                'empty-title'   => 'Inga produkter funna',
                'product-image' => 'Produktbild',
                'qty'           => ':qty Tillgängliga',
                'sku'           => 'SKU - :sku',
                'title'         => 'Välj Produkter',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Lägg Till Bild',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Generera Bild',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Endast bildfiler (.jpeg, .jpg, .png, ..) är tillåtna.',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Använd',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Generera',
                    'generating'       => 'Genererar...',
                    'hd'               => 'HD',
                    'model'            => 'Modell',
                    'number-of-images' => 'Antal Bilder',
                    'prompt'           => 'Prompt',
                    'quality'          => 'Kvalitet',
                    'regenerate'       => 'Regenerera',
                    'regenerating'     => 'Regenererar...',
                    'size'             => 'Storlek',
                    'standard'         => 'Standard',
                    'title'            => 'AI Bildgenerering',
                ],

                'placeholders' => [
                    'front'     => 'Fram',
                    'next'      => 'Nästa',
                    'size'      => 'Storlek',
                    'use-cases' => 'Användningsområden',
                    'zoom'      => 'Zooma',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Lägg Till Video',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Endast videofiler (.mp4, .mov, .ogg ..) är tillåtna.',
            ],

            'files' => [
                'add-file-btn'      => 'Lägg Till Fil',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'Endast PDF-filer är tillåtna',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                  => 'Använd',
                'generate'               => 'Generera',
                'generated-content'      => 'Genererat Innehåll',
                'generated-content-info' => 'AI-genererat innehåll kan vara vilseledande. Vänligen granska det genererade innehållet innan du använder det.',
                'generating'             => 'Genererar...',
                'prompt'                 => 'Prompt',
                'title'                  => 'AI Assistans',
                'model'                  => 'Modell',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 Uncensurerad',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],
];
