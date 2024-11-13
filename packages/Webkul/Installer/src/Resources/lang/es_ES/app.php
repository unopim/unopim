<?php

return [
    'seeders'   => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Por defecto',
            ],

            'attribute-groups'   => [
                'description'       => 'Descripción',
                'general'           => 'General',
                'inventories'       => 'Inventarios',
                'meta-description'  => 'Meta descripción',
                'price'             => 'Precio',
                'technical'         => 'Técnico',
                'shipping'          => 'Envío',
            ],

            'attributes'         => [
                'brand'                => 'Marca',
                'color'                => 'Color',
                'cost'                 => 'Costo',
                'description'          => 'Descripción',
                'featured'             => 'Presentado',
                'guest-checkout'       => 'Pago de invitado',
                'height'               => 'Altura',
                'length'               => 'Longitud',
                'manage-stock'         => 'Gestionar existencias',
                'meta-description'     => 'Meta descripción',
                'meta-keywords'        => 'Metapalabras clave',
                'meta-title'           => 'Metatítulo',
                'name'                 => 'Nombre',
                'new'                  => 'Nuevo',
                'price'                => 'Precio',
                'product-number'       => 'Número de producto',
                'short-description'    => 'Breve descripción',
                'size'                 => 'Tamaño',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Precio especial desde',
                'special-price-to'     => 'Precio especial para',
                'special-price'        => 'Precio especial',
                'status'               => 'Estado',
                'tax-category'         => 'Categoría de impuestos',
                'url-key'              => 'Clave de URL',
                'visible-individually' => 'Visible individualmente',
                'weight'               => 'Peso',
                'width'                => 'Ancho',
            ],

            'attribute-options'  => [
                'black'  => 'Negro',
                'green'  => 'Verde',
                'l'      => 'l',
                'm'      => 'METRO',
                'red'    => 'Rojo',
                's'      => 'S',
                'white'  => 'Blanco',
                'xl'     => 'SG',
                'yellow' => 'Amarillo',
            ],
        ],

        'category'  => [
            'categories' => [
                'description' => 'Descripción de la categoría raíz',
                'name'        => 'Raíz',
            ],

            'category_fields' => [
                'name'        => 'Nombre',
                'description' => 'Descripción',
            ],
        ],

        'cms'       => [
            'pages' => [
                'about-us'         => [
                    'content' => 'Acerca de nosotros Contenido de la página',
                    'title'   => 'Sobre nosotros',
                ],

                'contact-us'       => [
                    'content' => 'Contáctenos Contenido de la página',
                    'title'   => 'Contáctenos',
                ],

                'customer-service' => [
                    'content' => 'Contenido de la página de servicio al cliente',
                    'title'   => 'Servicio al cliente',
                ],

                'payment-policy'   => [
                    'content' => 'Contenido de la página de política de pago',
                    'title'   => 'Política de pago',
                ],

                'privacy-policy'   => [
                    'content' => 'Contenido de la página de política de privacidad',
                    'title'   => 'política de privacidad',
                ],

                'refund-policy'    => [
                    'content' => 'Contenido de la página de política de reembolso',
                    'title'   => 'Política de reembolso',
                ],

                'return-policy'    => [
                    'content' => 'Contenido de la página de política de devolución',
                    'title'   => 'Política de devoluciones',
                ],

                'shipping-policy'  => [
                    'content' => 'Contenido de la página de política de envío',
                    'title'   => 'Política de envío',
                ],

                'terms-conditions' => [
                    'content' => 'Términos y condiciones Contenido de la página',
                    'title'   => 'Términos y condiciones',
                ],

                'terms-of-use'     => [
                    'content' => 'Términos de uso Contenido de la página',
                    'title'   => 'Condiciones de uso',
                ],

                'whats-new'        => [
                    'content' => 'Contenido de la página Novedades',
                    'title'   => 'Qué hay de nuevo',
                ],
            ],
        ],

        'core'      => [
            'channels'   => [
                'meta-title'       => 'tienda de demostración',
                'meta-keywords'    => 'Meta palabra clave de la tienda de demostración',
                'meta-description' => 'Meta descripción de la tienda de demostración',
                'name'             => 'Por defecto',
            ],

            'currencies' => [
                'AED' => 'dírham',
                'AFN' => 'Shekel israelí',
                'CNY' => 'yuan chino',
                'EUR' => 'EURO',
                'GBP' => 'Libra esterlina',
                'INR' => 'rupia india',
                'IRR' => 'rial iraní',
                'JPY' => 'yen japonés',
                'RUB' => 'rublo ruso',
                'SAR' => 'Riyal saudí',
                'TRY' => 'lira turca',
                'UAH' => 'grivna ucraniana',
                'USD' => 'dólar estadounidense',
            ],
        ],

        'customer'  => [
            'customer-groups' => [
                'general'   => 'General',
                'guest'     => 'Invitado',
                'wholesale' => 'Al por mayor',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Por defecto',
            ],
        ],

        'shop'      => [
            'theme-customizations' => [
                'all-products'           => [
                    'name'    => 'Todos los productos',

                    'options' => [
                        'title' => 'Todos los productos',
                    ],
                ],

                'bold-collections'       => [
                    'content' => [
                        'btn-title'   => 'Ver todo',
                        'description' => '¡Presentamos nuestras nuevas colecciones atrevidas! Eleva tu estilo con diseños atrevidos y declaraciones vibrantes. Explora patrones llamativos y colores llamativos que redefinen tu guardarropa. ¡Prepárate para abrazar lo extraordinario!',
                        'title'       => '¡Prepárate para nuestras nuevas colecciones atrevidas!',
                    ],

                    'name'    => 'Colecciones atrevidas',
                ],

                'categories-collections' => [
                    'name' => 'Categorías Colecciones',
                ],

                'featured-collections'   => [
                    'name'    => 'Colecciones destacadas',

                    'options' => [
                        'title' => 'Productos destacados',
                    ],
                ],

                'footer-links'           => [
                    'name'    => 'Enlaces de pie de página',

                    'options' => [
                        'about-us'         => 'Sobre nosotros',
                        'contact-us'       => 'Contáctenos',
                        'customer-service' => 'Servicio al cliente',
                        'payment-policy'   => 'Política de pago',
                        'privacy-policy'   => 'política de privacidad',
                        'refund-policy'    => 'Política de reembolso',
                        'return-policy'    => 'Política de devoluciones',
                        'shipping-policy'  => 'Política de envío',
                        'terms-conditions' => 'Términos y condiciones',
                        'terms-of-use'     => 'Condiciones de uso',
                        'whats-new'        => 'Qué hay de nuevo',
                    ],
                ],

                'game-container'         => [
                    'content' => [
                        'sub-title-1' => 'Nuestras Colecciones',
                        'sub-title-2' => 'Nuestras Colecciones',
                        'title'       => '¡El juego con nuestras nuevas incorporaciones!',
                    ],

                    'name'    => 'Contenedor de juego',
                ],

                'image-carousel'         => [
                    'name'    => 'Carrusel de imágenes',

                    'sliders' => [
                        'title' => 'Prepárese para la nueva colección',
                    ],
                ],

                'new-products'           => [
                    'name'    => 'Nuevos productos',

                    'options' => [
                        'title' => 'Nuevos productos',
                    ],
                ],

                'offer-information'      => [
                    'content' => [
                        'title' => 'Obtenga HASTA 40 % de descuento en su primer pedido COMPRAR AHORA',
                    ],

                    'name' => 'Información de la oferta',
                ],

                'services-content'       => [
                    'description' => [
                        'emi-available-info'   => 'EMI sin costo disponible en todas las principales tarjetas de crédito',
                        'free-shipping-info'   => 'Disfrute del envío gratuito en todos los pedidos',
                        'product-replace-info' => '¡Fácil reemplazo de producto disponible!',
                        'time-support-info'    => 'Soporte dedicado 24 horas al día, 7 días a la semana a través de chat y correo electrónico',
                    ],

                    'name'        => 'Contenido de servicios',

                    'title'       => [
                        'emi-available'   => 'Emi disponible',
                        'free-shipping'   => 'Envío gratis',
                        'product-replace' => 'Reemplazo de producto',
                        'time-support'    => 'Soporte 24 horas al día, 7 días a la semana',
                    ],
                ],

                'top-collections'        => [
                    'content' => [
                        'sub-title-1' => 'Nuestras Colecciones',
                        'sub-title-2' => 'Nuestras Colecciones',
                        'sub-title-3' => 'Nuestras Colecciones',
                        'sub-title-4' => 'Nuestras Colecciones',
                        'sub-title-5' => 'Nuestras Colecciones',
                        'sub-title-6' => 'Nuestras Colecciones',
                        'title'       => '¡El juego con nuestras nuevas incorporaciones!',
                    ],

                    'name'    => 'Colecciones principales',
                ],
            ],
        ],

        'user'      => [
            'roles' => [
                'description' => 'Los usuarios de este rol tendrán todo el acceso.',
                'name'        => 'Administrador',
            ],

            'users' => [
                'name' => 'Ejemplo',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administración',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'confirmar Contraseña',
                'email-address'    => 'administrador@ejemplo.com',
                'email'            => 'Correo electrónico',
                'password'         => 'Contraseña',
                'title'            => 'Crear administrador',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Monedas permitidas',
                'allowed-locales'     => 'Localidades permitidas',
                'application-name'    => 'Nombre de la aplicación',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Yuan chino (CNY)',
                'database-connection' => 'Conexión de base de datos',
                'database-hostname'   => 'Nombre de host de la base de datos',
                'database-name'       => 'Nombre de la base de datos',
                'database-password'   => 'Contraseña de la base de datos',
                'database-port'       => 'Puerto de base de datos',
                'database-prefix'     => 'Prefijo de base de datos',
                'database-username'   => 'Nombre de usuario de la base de datos',
                'default-currency'    => 'Moneda predeterminada',
                'default-locale'      => 'Configuración regional predeterminada',
                'default-timezone'    => 'Zona horaria predeterminada',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL predeterminada',
                'dirham'              => 'Dírham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Rial iraní (IRR)',
                'israeli'             => 'Shekel israelí (AFN)',
                'japanese-yen'        => 'Yen japonés (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Libra esterlina (GBP)',
                'rupee'               => 'Rupia india (INR)',
                'russian-ruble'       => 'Rublo ruso (RUB)',
                'saudi'               => 'Riyal saudí (SAR)',
                'select-timezone'     => 'Seleccionar zona horaria',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Configuración de base de datos',
                'turkish-lira'        => 'Lira turca (TRY)',
                'ukrainian-hryvnia'   => 'Grivna ucraniana (UAH)',
                'usd'                 => 'Dólar estadounidense (USD)',
                'warning-message'     => '¡Tener cuidado! La configuración de los idiomas predeterminados del sistema, así como la moneda predeterminada, son permanentes y no se pueden cambiar nunca más.',
            ],

            'installation-processing'   => [
                'unopim'            => 'Instalación UnoPim',
                'unopim-info'       => 'Crear las tablas de la base de datos, esto puede llevar unos momentos.',
                'title'             => 'Instalación',
            ],

            'installation-completed'    => [
                'admin-panel'                   => 'Panel de administración',
                'unopim-forums'                 => 'Foro UnoPim',
                'explore-unopim-extensions'     => 'Explora la extensión UnoPim',
                'title-info'                    => 'UnoPim se instaló correctamente en su sistema.',
                'title'                         => 'Instalación completada',
            ],

            'ready-for-installation'    => [
                'create-databsae-table'   => 'Crear la tabla de base de datos',
                'install-info-button'     => 'Haga clic en el botón de abajo para',
                'install-info'            => 'UnoPim para instalación',
                'install'                 => 'Instalación',
                'populate-database-table' => 'Llenar las tablas de la base de datos',
                'start-installation'      => 'Iniciar instalación',
                'title'                   => 'Listo para la instalación',
            ],

            'start'                     => [
                'locale'        => 'Lugar',
                'main'          => 'Comenzar',
                'select-locale' => 'Seleccionar configuración regional',
                'title'         => 'Su instalación de UnoPim',
                'welcome-title' => 'Bienvenido a UnoPim :version',
            ],

            'server-requirements'       => [
                'calendar'    => 'Calendario',
                'ctype'       => 'cTipo',
                'curl'        => 'rizo',
                'dom'         => 'dominación',
                'fileinfo'    => 'información de archivo',
                'filter'      => 'Filtrar',
                'gd'          => 'Dios',
                'hash'        => 'Picadillo',
                'intl'        => 'internacional',
                'json'        => 'JSON',
                'mbstring'    => 'cadenamb',
                'openssl'     => 'abresl',
                'pcre'        => 'pcre',
                'pdo'         => 'dop',
                'php-version' => '8.2 o superior',
                'php'         => 'PHP',
                'session'     => 'sesión',
                'title'       => 'Requisitos del sistema',
                'tokenizer'   => 'tokenizador',
                'xml'         => 'XML',
            ],

            'arabic'                    => 'árabe',
            'back'                      => 'Atrás',
            'unopim-info'               => 'un proyecto comunitario de',
            'unopim-logo'               => 'Logotipo de UnoPim',
            'unopim'                    => 'UnoPim',
            'bengali'                   => 'bengalí',
            'chinese'                   => 'Chino',
            'continue'                  => 'Continuar',
            'dutch'                     => 'Holandés',
            'english'                   => 'Inglés',
            'french'                    => 'Francés',
            'german'                    => 'Alemán',
            'hebrew'                    => 'hebreo',
            'hindi'                     => 'hindi',
            'installation-description'  => 'La instalación de UnoPim normalmente implica varios pasos. Aquí hay un resumen general del proceso de instalación de UnoPim:',
            'wizard-language'           => 'Idioma del asistente de instalación',
            'installation-info'         => '¡Estamos felices de verte aquí!',
            'installation-title'        => 'Bienvenido a la instalación',
            'italian'                   => 'italiano',
            'japanese'                  => 'japonés',
            'persian'                   => 'persa',
            'polish'                    => 'Polaco',
            'portuguese'                => 'portugués brasileño',
            'russian'                   => 'ruso',
            'save-configuration'        => 'Guardar configuración',
            'sinhala'                   => 'cingalés',
            'skip'                      => 'Saltar',
            'spanish'                   => 'Español',
            'title'                     => 'Instalador de UnoPim',
            'turkish'                   => 'turco',
            'ukrainian'                 => 'ucranio',
            'webkul'                    => 'Webkul',
        ],
    ],
];
