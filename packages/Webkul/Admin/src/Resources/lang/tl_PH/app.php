<?php

return [
    'users' => [
        'sessions' => [
            'email'                => 'Email Address',
            'forget-password-link' => 'Nakalimutan ang password?',
            'password'             => 'Password',
            'submit-btn'           => 'Mag-sign In',
            'title'                => 'Mag-sign In',
        ],

        'forget-password' => [
            'create' => [
                'email'                => 'Nai-rehistro na Email',
                'email-not-exist'      => 'Walang ganitong Email',
                'page-title'           => 'Nakalimutan ang password',
                'reset-link-sent'      => 'Link para sa pag-reset ng password ay naipadala',
                'email-settings-error' => 'Hindi mapadala ang Email. Pakitingnan ang inyong email configuration details',
                'sign-in-link'         => 'Bumalik sa Mag-sign In?',
                'submit-btn'           => 'I-reset',
                'title'                => 'I-recover ang password',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => 'Bumalik sa Mag-sign In?',
            'confirm-password' => 'Kumpirmahin ang Password',
            'email'            => 'Nai-rehistro na Email',
            'password'         => 'Password',
            'submit-btn'       => 'I-reset ang Password',
            'title'            => 'I-reset ang Password',
        ],
    ],

    'notifications' => [
        'description-text' => 'Listahan ng lahat ng Notifications',
        'marked-success'   => 'Notification na Marked bilang Nabasang Tagumpay',
        'no-record'        => 'Walang Record na Natagpuan',
        'read-all'         => 'Markahan bilang Basang Lahat',
        'title'            => 'Notifications',
        'view-all'         => 'Tingnan ang Lahat',
        'status'           => [
            'all'        => 'Lahat',
            'canceled'   => 'Kinansela',
            'closed'     => 'Sarado',
            'completed'  => 'Nakatapos',
            'pending'    => 'Nasa Paghihintay',
            'processing' => 'Pinoproseso',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => 'Bumalik',
            'change-password'   => 'Baguhin ang Password',
            'confirm-password'  => 'Kumpirmahin ang Password',
            'current-password'  => 'Kasalukuyang Password',
            'email'             => 'Email',
            'general'           => 'Pangkalahatan',
            'invalid-password'  => 'Ang kasalukuyang password na iyong ipinasok ay mali.',
            'name'              => 'Pangalan',
            'password'          => 'Password',
            'profile-image'     => 'Larawan ng Profile',
            'save-btn'          => 'I-save ang Account',
            'title'             => 'Aking Account',
            'ui-locale'         => 'Lokalisasyon ng UI',
            'update-success'    => 'Na-update ang Account na matagumpay',
            'upload-image-info' => 'I-upload ang isang Larawan ng Profile (110px X 110px)',
            'user-timezone'     => 'Oras ng User',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => 'Dashboard',
            'user-info'        => 'Madaling pag-momonitor kung ano ang bilang sa iyong PIM',
            'user-name'        => 'Hi! :user_name',
            'catalog-details'  => 'Catalog',
            'total-families'   => 'Mga Kabuuang Pamilya',
            'total-attributes' => 'Mga Kabuuang Atributo',
            'total-groups'     => 'Mga Kabuuang Grupo',
            'total-categories' => 'Mga Kabuuang Kategorya',
            'total-products'   => 'Mga Kabuuang Produkto',
            'settings-details' => 'Estruktura ng Catalog',
            'total-locales'    => 'Mga Kabuuang Lokasyon',
            'total-currencies' => 'Mga Kabuuang Pera',
            'total-channels'   => 'Mga Kabuuang Channels',
        ],
    ],

    'acl' => [
        'addresses'                => 'Mga Address',
        'attribute-families'       => 'Mga Pamilya ng Katangian',
        'attribute-groups'         => 'Mga Grupo ng Katangian',
        'attributes'               => 'Mga Katangian',
        'cancel'                   => 'Kanselahin',
        'catalog'                  => 'Katalogo',
        'categories'               => 'Mga Kategorya',
        'channels'                 => 'Mga Channel',
        'configure'                => 'Pag-configure',
        'configuration'            => 'Pag-configure',
        'copy'                     => 'Kopyahin',
        'create'                   => 'Lumikha',
        'currencies'               => 'Mga Pera',
        'dashboard'                => 'Dashboard',
        'data-transfer'            => 'Paglipat ng Data',
        'delete'                   => 'Burahin',
        'edit'                     => 'I-edit',
        'email-templates'          => 'Mga Template ng Email',
        'events'                   => 'Mga Kaganapan',
        'groups'                   => 'Mga Grupo',
        'import'                   => 'Mag-import',
        'imports'                  => 'Mga Pag-import',
        'invoices'                 => 'Mga Invoice',
        'locales'                  => 'Mga Lokasyon',
        'magic-ai'                 => 'Magic AI',
        'marketing'                => 'Marketing',
        'newsletter-subscriptions' => 'Mga Subscription sa Newsletter',
        'note'                     => 'Nota',
        'orders'                   => 'Mga Order',
        'products'                 => 'Mga Produkto',
        'promotions'               => 'Mga Promo',
        'refunds'                  => 'Mga Refund',
        'reporting'                => 'Pag-uulat',
        'reviews'                  => 'Mga Pagsusuri',
        'roles'                    => 'Mga Papel',
        'sales'                    => 'Mga Benta',
        'search-seo'               => 'Paghahanap & SEO',
        'search-synonyms'          => 'Mga Sinonim ng Paghahanap',
        'search-terms'             => 'Mga Termino sa Paghahanap',
        'settings'                 => 'Mga Setting',
        'shipments'                => 'Mga Pagpapadala',
        'sitemaps'                 => 'Mga Sitemap',
        'subscribers'              => 'Mga Subscriber',
        'tax-categories'           => 'Mga Kategorya ng Buwis',
        'tax-rates'                => 'Mga Rate ng Buwis',
        'taxes'                    => 'Mga Buwis',
        'themes'                   => 'Mga Tema',
        'integration'              => 'Integrasyon',
        'url-rewrites'             => 'Mga Rewritten na URL',
        'users'                    => 'Mga Gumagamit',
        'category_fields'          => 'Mga Field ng Kategorya',
        'view'                     => 'Tingnan',
        'execute'                  => 'Isagawa',
        'history'                  => 'Kasaysayan',
        'restore'                  => 'Ibalik',
        'integrations'             => 'Mga Integrasyon',
        'api'                      => 'API',
        'tracker'                  => 'Tagasunod ng Trabaho',
        'imports'                  => 'Mga Pag-import',
        'exports'                  => 'Mga Pag-export',
    ],

    'errors' => [
        'dashboard' => 'Dashboard',
        'go-back'   => 'Bumalik',
        'support'   => 'Kung ang problema ay nagpapatuloy, makipag-ugnayan sa amin sa <a href=":link" class=":class">:email</a> para sa tulong.',

        '404' => [
            'description' => 'Oops! Ang pahinang hinahanap mo ay nasa bakasyon. Mukhang hindi namin mahanap ang hinahanap mo.',
            'title'       => '404 Pahina ng Hindi Natagpuan',
        ],

        '401' => [
            'description' => 'Oops! Mukhang wala kang permiso na ma-access ang pahinang ito. Mukhang kulang ka ng mga kredensyal na kailangan.',
            'title'       => '401 Hindi Pinahintulutan',
            'message'     => 'Nabigo ang pag-a-authenticate dahil sa maling mga kredensyal o expired na token.',
        ],

        '403' => [
            'description' => 'Oops! Hindi ma-access ang pahinang ito. Mukhang wala kang mga permiso para makita ang nilalaman na ito.',
            'title'       => '403 Ipinagbabawal',
        ],

        '413' => [
            'description' => 'Oops! Mukhang sinusubukan mong mag-upload ng isang napakalaking file. Kung gusto mong i-upload ito, i-update ang configuration ng PHP.',
            'title'       => '413 Napakalaking Nilalaman',
        ],

        '419' => [
            'description' => 'Oops! Nag-expire ang iyong session. Mangyaring i-refresh ang pahina at mag-login muli para magpatuloy.',
            'title'       => '419 Nag-expire na Sesyon',
        ],

        '500' => [
            'description' => 'Oops! May nangyaring mali. Mukhang may problema kami sa pag-load ng pahinang hinahanap mo.',
            'title'       => '500 Error ng Server',
        ],

        '503' => [
            'description' => 'Oops! Mukhang pansamantalang sarado tayo para sa maintenance. Mag-check ulit mamaya.',
            'title'       => '503 Hindi Magagamit na Serbisyo',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => 'I-download',
        'export'     => 'Quick Export',
        'no-records' => 'Walang data na i-export',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => 'Ang slug na ito ay ginagamit sa mga kategorya o produkto.',
        'slug-reserved'   => 'Ang slug na ito ay na-reserba.',
        'invalid-locale'  => 'Invalid na mga lokasyon :locales',
    ],

    'footer' => [
        'copy-right' => 'Pinapagana ng <a href="https://unopim.com/" target="_blank">UnoPim</a>, Isang Komunidad na Proyekto mula sa <a href="https://webkul.com/" target="_blank">Webkul</a>',
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
        'yes'     => 'Oo',
        'no'      => 'Hindi',
        'true'    => 'Totoo',
        'false'   => 'Hindi Totoo',
        'enable'  => 'Pinagana',
        'disable' => 'Walang Bisa',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => 'Tanggalin',
            'no-result-found'              => 'Walang resulta',
            'save-btn'                     => 'I-save ang Configurations',
            'save-message'                 => 'Nai-save na ang Configuration',
            'search'                       => 'Maghanap',
            'title'                        => 'Configurations',

            'general' => [
                'info'  => '',
                'title' => 'Pangkalahatan',

                'general' => [
                    'info'  => '',
                    'title' => 'Pangkalahatan',
                ],

                'magic-ai' => [
                    'info'  => 'Itakda ang mga opsyon ng Magic AI.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API Key',
                        'enabled'        => 'Pinagana',
                        'llm-api-domain' => 'Domain ng API ng LLM',
                        'organization'   => 'ID ng Organisasyon',
                        'title'          => 'Mga Pangunahing Setting',
                        'title-info'     => 'Palakihin ang iyong karanasan sa Magic AI sa pamamagitan ng pagpasok ng iyong eksklusibong API Key at pag-indicate ng kaukulang Organisasyon para sa walang putol na pagsasama. Kunin ang kontrol sa iyong mga kredensyal ng OpenAI at i-customize ang mga setting ayon sa iyong mga partikular na pangangailangan.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => 'Lumikha',
                'title'      => 'Mga Integrasyon',

                'datagrid' => [
                    'delete'          => 'Tanggalin',
                    'edit'            => 'I-edit',
                    'id'              => 'ID',
                    'name'            => 'Pangalan',
                    'user'            => 'Tagagamit',
                    'client-id'       => 'ID ng Kliyente',
                    'permission-type' => 'Uri ng Pagpapahintulot',
                ],
            ],

            'create' => [
                'access-control' => 'Kontrol sa Pag-access',
                'all'            => 'Lahat',
                'back-btn'       => 'Bumalik',
                'custom'         => 'Pasadya',
                'assign-user'    => 'Mag-assign ng Tagagamit',
                'general'        => 'Pangkalahatan',
                'name'           => 'Pangalan',
                'permissions'    => 'Mga Pagpapahintulot',
                'save-btn'       => 'I-save',
                'title'          => 'Bagong Integrasyon',
            ],

            'edit' => [
                'access-control' => 'Kontrol sa Pag-access',
                'all'            => 'Lahat',
                'back-btn'       => 'Bumalik',
                'custom'         => 'Pasadya',
                'assign-user'    => 'Mag-assign ng Tagagamit',
                'general'        => 'Pangkalahatan',
                'name'           => 'Pangalan',
                'credentials'    => 'Mga Kredensyal',
                'client-id'      => 'ID ng Kliyente',
                'secret-key'     => 'Sekreto Key',
                'generate-btn'   => 'Gumawa',
                're-secret-btn'  => 'Re-Gumawa ng Sekreto Key',
                'permissions'    => 'Mga Pagpapahintulot',
                'save-btn'       => 'I-save',
                'title'          => 'I-edit ang Integrasyon',
            ],

            'being-used'                     => 'Ang API Integration ay ginagamit na ng Admin User',
            'create-success'                 => 'Ang API Integration ay matagumpay na nilikha',
            'delete-failed'                  => 'Ang API Integration ay hindi matagumpay na natanggal',
            'delete-success'                 => 'Ang API Integration ay matagumpay na natanggal',
            'last-delete-error'              => 'Ang huling API Integration ay hindi maaaring tanggalin',
            'update-success'                 => 'Ang API Integration ay matagumpay na na-update',
            'generate-key-success'           => 'Ang API Key ay matagumpay na nagenereate',
            're-generate-secret-key-success' => 'Ang API secret key ay matagumpay na na-regenerate',
            'client-not-found'               => 'Ang client ay hindi natagpuan',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Account',
                'app-version'   => 'Version : :version',
                'logout'        => 'Logout',
                'my-account'    => 'My Account',
                'notifications' => 'Notifications',
                'visit-shop'    => 'Visit Shop',
            ],

            'sidebar' => [
                'attribute-families'       => 'Attribute Families',
                'attribute-groups'         => 'Attribute Groups',
                'attributes'               => 'Attributes',
                'history'                  => 'History',
                'edit-section'             => 'Data',
                'general'                  => 'General',
                'catalog'                  => 'Catalog',
                'categories'               => 'Categories',
                'category_fields'          => 'Category Fields',
                'channels'                 => 'Channels',
                'collapse'                 => 'Collapse',
                'configure'                => 'Configuration',
                'currencies'               => 'Currencies',
                'dashboard'                => 'Dashboard',
                'data-transfer'            => 'Data Transfer',
                'groups'                   => 'Groups',
                'tracker'                  => 'Job Tracker',
                'imports'                  => 'Imports',
                'exports'                  => 'Exports',
                'locales'                  => 'Locales',
                'magic-ai'                 => 'Magic AI',
                'mode'                     => 'Dark Mode',
                'products'                 => 'Products',
                'roles'                    => 'Roles',
                'settings'                 => 'Settings',
                'themes'                   => 'Themes',
                'users'                    => 'Users',
                'integrations'             => 'Integrations',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'No records have been selected.',
                'must-select-a-mass-action-option' => 'You must select a mass action\'s option.',
                'must-select-a-mass-action'        => 'You must select a mass action.',
            ],

            'toolbar' => [
                'length-of' => ':length of',
                'of'        => 'of',
                'per-page'  => 'Per Page',
                'results'   => ':total Results',
                'selected'  => ':total Selected',

                'mass-actions' => [
                    'submit'        => 'Submit',
                    'select-option' => 'Select Option',
                    'select-action' => 'Select Action',
                ],

                'filter' => [
                    'title' => 'Filter',
                ],

                'search_by' => [
                    'code'       => 'Search by code',
                    'code_or_id' => 'Search by code or id',
                ],

                'search' => [
                    'title' => 'Search',
                ],
            ],

            'filters' => [
                'select'   => 'Select',
                'title'    => 'Apply Filters',
                'save'     => 'Save',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Type atleast 2 characters...',
                        'no-results'        => 'No result found...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Clear All',
                    'title'     => 'Custom Filters',
                ],

                'boolean-options' => [
                    'false' => 'False',
                    'true'  => 'True',
                ],

                'date-options' => [
                    'last-month'        => 'Last Month',
                    'last-six-months'   => 'Last 6 Months',
                    'last-three-months' => 'Last 3 Months',
                    'this-month'        => 'This Month',
                    'this-week'         => 'This Week',
                    'this-year'         => 'This Year',
                    'today'             => 'Today',
                    'yesterday'         => 'Yesterday',
                ],
            ],

            'table' => [
                'actions'              => 'Actions',
                'no-records-available' => 'No Records Available.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Agree',
                'disagree-btn' => 'Disagree',
                'message'      => 'Are you sure you want to perform this action?',
                'title'        => 'Are you sure?',
            ],

            'delete' => [
                'agree-btn'    => 'Delete',
                'disagree-btn' => 'Cancel',
                'message'      => 'Are you sure you want to delete?',
                'title'        => 'Confirm Deletion',
            ],

            'history' => [
                'title'           => 'History Preview',
                'subtitle'        => 'Quickly review your updates and changes.',
                'close-btn'       => 'Close',
                'version-label'   => 'Version',
                'date-time-label' => 'Date/Time',
                'user-label'      => 'User',
                'name-label'      => 'Key',
                'old-value-label' => 'Old Value',
                'new-value-label' => 'New Value',
                'no-history'      => 'No history Found',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Add Selected Product',
                'empty-info'    => 'No products available for search term.',
                'empty-title'   => 'No products found',
                'product-image' => 'Product Image',
                'qty'           => ':qty Available',
                'sku'           => 'SKU - :sku',
                'title'         => 'Select Products',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Add Image',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Generate Image',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Only images files (.jpeg, .jpg, .png, ..) are allowed.',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Apply',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Generate',
                    'generating'       => 'Generating...',
                    'hd'               => 'HD',
                    'model'            => 'Model',
                    'number-of-images' => 'Number of Images',
                    'prompt'           => 'Prompt',
                    'quality'          => 'Quality',
                    'regenerate'       => 'Regenerate',
                    'regenerating'     => 'Regenerating...',
                    'size'             => 'Size',
                    'standard'         => 'Standard',
                    'title'            => 'AI Image Generation',
                ],

                'placeholders' => [
                    'front'     => 'Front',
                    'next'      => 'Next',
                    'size'      => 'Size',
                    'use-cases' => 'Use Cases',
                    'zoom'      => 'Zoom',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Add Video',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Only videos files (.mp4, .mov, .ogg ..) are allowed.',
            ],

            'files' => [
                'add-file-btn'      => 'Add File',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'Only pdf files are allowed',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                  => 'Apply',
                'generate'               => 'Generate',
                'generated-content'      => 'Generated Content',
                'generated-content-info' => 'AI content can be misleading. Please review the generated content before applying it.',
                'generating'             => 'Generating...',
                'prompt'                 => 'Prompt',
                'title'                  => 'AI Assistance',
                'model'                  => 'Model',
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
