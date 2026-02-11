<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Por defecto',
            ],

            'attribute-groups' => [
                'description'      => 'Descripción',
                'general'          => 'General',
                'meta-description' => 'Meta descripción',
                'price'            => 'Precio',
                'media'            => 'Medios',
            ],

            'attributes' => [
                'brand'                => 'Marca',
                'color'                => 'Color',
                'cost'                 => 'Costo',
                'description'          => 'Descripción',
                'featured'             => 'Presentado',
                'guest-checkout'       => 'Pago de invitado',
                'height'               => 'Altura',
                'image'                => 'Imagen',
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
                'tax-category'         => 'Categoría de impuestos',
                'url-key'              => 'Clave de URL',
                'visible-individually' => 'Visible individualmente',
                'weight'               => 'Peso',
                'width'                => 'Ancho',
            ],

            'attribute-options' => [
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

        'category' => [
            'categories' => [
                'description' => 'Descripción de la categoría raíz',
                'name'        => 'Raíz',
            ],

            'category_fields' => [
                'name'        => 'Nombre',
                'description' => 'Descripción',
            ],
        ],

        'core' => [
            'channels' => [
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

        'user' => [
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
        'middleware' => [
            'already-installed' => 'La aplicación ya está instalada.',
        ],

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

            'installation-processing' => [
                'unopim'      => 'Instalación UnoPim',
                'unopim-info' => 'Crear las tablas de la base de datos, esto puede llevar unos momentos.',
                'title'       => 'Instalación',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Panel de administración',
                'unopim-forums'             => 'Foro UnoPim',
                'explore-unopim-extensions' => 'Explora la extensión UnoPim',
                'title-info'                => 'UnoPim se instaló correctamente en su sistema.',
                'title'                     => 'Instalación completada',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Crear la tabla de base de datos',
                'install-info-button'     => 'Haga clic en el botón de abajo para',
                'install-info'            => 'UnoPim para instalación',
                'install'                 => 'Instalación',
                'populate-database-table' => 'Llenar las tablas de la base de datos',
                'start-installation'      => 'Iniciar instalación',
                'title'                   => 'Listo para la instalación',
            ],

            'start' => [
                'locale'        => 'Lugar',
                'main'          => 'Comenzar',
                'select-locale' => 'Seleccionar configuración regional',
                'title'         => 'Su instalación de UnoPim',
                'welcome-title' => 'Bienvenido a UnoPim :version',
            ],

            'server-requirements' => [
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

            'back'                     => 'Atrás',
            'unopim-info'              => 'un proyecto comunitario de',
            'unopim-logo'              => 'Logotipo de UnoPim',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Continuar',
            'installation-description' => 'La instalación de UnoPim normalmente implica varios pasos. Aquí hay un resumen general del proceso de instalación de UnoPim:',
            'wizard-language'          => 'Idioma del asistente de instalación',
            'installation-info'        => '¡Estamos felices de verte aquí!',
            'installation-title'       => 'Bienvenido a la instalación',
            'save-configuration'       => 'Guardar configuración',
            'skip'                     => 'Saltar',
            'title'                    => 'Instalador de UnoPim',
            'webkul'                   => 'Webkul',
        ],
    ],
];
