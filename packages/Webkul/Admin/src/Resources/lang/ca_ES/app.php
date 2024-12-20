<?php

return [
    'users' => [
        'sessions' => [
            'email'                => 'Adreça electrònica',
            'forget-password-link' => 'Oblidar contrasenya?',
            'password'             => 'Contrasenya',
            'submit-btn'           => 'Iniciar sessió',
            'title'                => 'Iniciar sessió',
        ],

        'forget-password' => [
            'create' => [
                'email'                => 'Correu electrònic registrat',
                'email-not-exist'      => 'El correu no existeix',
                'page-title'           => 'Oblidar contrasenya',
                'reset-link-sent'      => 'Enllaç per restablir la contrasenya enviat',
                'email-settings-error' => 'No s’ha pogut enviar el correu electrònic. Reviseu els detalls de configuració del correu',
                'sign-in-link'         => 'Tornar a Iniciar sessió?',
                'submit-btn'           => 'Restablir',
                'title'                => 'Recuperar contrasenya',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => 'Tornar a Iniciar sessió?',
            'confirm-password' => 'Confirmar contrasenya',
            'email'            => 'Correu electrònic registrat',
            'password'         => 'Contrasenya',
            'submit-btn'       => 'Restablir contrasenya',
            'title'            => 'Restablir contrasenya',
        ],
    ],

    'notifications' => [
        'description-text' => 'Llistar totes les notificacions',
        'marked-success'   => 'Notificació marcada correctament',
        'no-record'        => 'No s’ha trobat cap registre',
        'read-all'         => 'Marcar com a llegides',
        'title'            => 'Notificacions',
        'view-all'         => 'Veure totes',
        'status'           => [
            'all'        => 'Totes',
            'canceled'   => 'Cancel·lades',
            'closed'     => 'Tancades',
            'completed'  => 'Completades',
            'pending'    => 'Pendents',
            'processing' => 'En procés',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => 'Enrere',
            'change-password'   => 'Canviar contrasenya',
            'confirm-password'  => 'Confirmar contrasenya',
            'current-password'  => 'Contrasenya actual',
            'email'             => 'Correu electrònic',
            'general'           => 'General',
            'invalid-password'  => 'La contrasenya actual introduïda és incorrecta.',
            'name'              => 'Nom',
            'password'          => 'Contrasenya',
            'profile-image'     => 'Imatge del perfil',
            'save-btn'          => 'Desar compte',
            'title'             => 'El meu compte',
            'ui-locale'         => 'Interfície local',
            'update-success'    => 'Compte actualitzat correctament',
            'upload-image-info' => 'Puja una imatge de perfil (110px X 110px)',
            'user-timezone'     => 'Fus horari',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => 'Tauler de control',
            'user-info'        => 'Monitorització ràpida, què compta al vostre PIM',
            'user-name'        => 'Hola! :user_name',
            'catalog-details'  => 'Catàleg',
            'total-families'   => 'Total de famílies',
            'total-attributes' => 'Total d’atributs',
            'total-groups'     => 'Total de grups',
            'total-categories' => 'Total de categories',
            'total-products'   => 'Total de productes',
            'settings-details' => 'Estructura del catàleg',
            'total-locales'    => 'Total de locals',
            'total-currencies' => 'Total de monedes',
            'total-channels'   => 'Total de canals',
        ],
    ],

    'configuration' => [
        'index' => [
            'delete'                       => 'Eliminar',
            'no-result-found'              => 'Nenhum resultado encontrado',
            'save-btn'                     => 'Salvar configuração',
            'save-message'                 => 'Configuração salva com sucesso',
            'search'                       => 'Pesquisar',
            'title'                        => 'Configuração',

            'general' => [
                'info'  => '',
                'title' => 'Geral',

                'general' => [
                    'info'  => '',
                    'title' => 'Geral',
                ],

                'magic-ai' => [
                    'info'  => 'Defina as opções do Magic AI.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'Chave API',
                        'enabled'        => 'Habilitado',
                        'llm-api-domain' => 'Domínio da API LLM',
                        'organization'   => 'ID da organização',
                        'title'          => 'Configurações gerais',
                        'title-info'     => 'Melhore sua experiência com a funcionalidade Magic AI inserindo sua chave API exclusiva e indicando a organização pertinente para integração sem problemas. Assuma o controle de suas credenciais OpenAI e personalize as configurações conforme suas necessidades específicas.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => 'Criar',
                'title'      => 'Integrações',

                'datagrid' => [
                    'delete'          => 'Excluir',
                    'edit'            => 'Editar',
                    'id'              => 'ID',
                    'name'            => 'Nome',
                    'user'            => 'Usuário',
                    'client-id'       => 'ID do cliente',
                    'permission-type' => 'Tipo de permissão',
                ],
            ],

            'create' => [
                'access-control' => 'Controle de Acesso',
                'all'            => 'Todos',
                'back-btn'       => 'Voltar',
                'custom'         => 'Personalizado',
                'assign-user'    => 'Atribuir Usuário',
                'general'        => 'Geral',
                'name'           => 'Nome',
                'permissions'    => 'Permissões',
                'save-btn'       => 'Salvar',
                'title'          => 'Nova Integração',
            ],

            'edit' => [
                'access-control' => 'Controle de Acesso',
                'all'            => 'Todos',
                'back-btn'       => 'Voltar',
                'custom'         => 'Personalizado',
                'assign-user'    => 'Atribuir Usuário',
                'general'        => 'Geral',
                'name'           => 'Nome',
                'credentials'    => 'Credenciais',
                'client-id'      => 'ID do Cliente',
                'secret-key'     => 'Chave Secreta',
                'generate-btn'   => 'Gerar',
                're-secret-btn'  => 'Regenerar Chave Secreta',
                'permissions'    => 'Permissões',
                'save-btn'       => 'Salvar',
                'title'          => 'Editar Integração',
            ],

            'being-used'                     => 'A Integração API já está em uso no usuário administrador',
            'create-success'                 => 'Integração API Criada com Sucesso',
            'delete-failed'                  => 'Integração API Não Excluída com Sucesso',
            'delete-success'                 => 'Integração API Excluída com Sucesso',
            'last-delete-error'              => 'Última Integração API Não Pode Ser Excluída',
            'update-success'                 => 'Integração API Atualizada com Sucesso',
            'generate-key-success'           => 'Chave API Gerada com Sucesso',
            're-generate-secret-key-success' => 'Chave Secreta da API Regenerada com Sucesso',
            'client-not-found'               => 'Cliente Não Encontrado',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Cuenta',
                'app-version'   => 'Versión : :version',
                'logout'        => 'Cerrar sesión',
                'my-account'    => 'Mi cuenta',
                'notifications' => 'Notificaciones',
                'visit-shop'    => 'Visitar tienda',
            ],

            'sidebar' => [
                'attribute-families'       => 'Familias de atributos',
                'attribute-groups'         => 'Grupos de atributos',
                'attributes'               => 'Atributos',
                'history'                  => 'Historial',
                'edit-section'             => 'Datos',
                'general'                  => 'General',
                'catalog'                  => 'Catálogo',
                'categories'               => 'Categorías',
                'category_fields'          => 'Campos de categoría',
                'channels'                 => 'Canales',
                'collapse'                 => 'Colapsar',
                'configure'                => 'Configuración',
                'currencies'               => 'Monedas',
                'dashboard'                => 'Panel de control',
                'data-transfer'            => 'Transferencia de datos',
                'groups'                   => 'Grupos',
                'tracker'                  => 'Rastreador de trabajos',
                'imports'                  => 'Importaciones',
                'exports'                  => 'Exportaciones',
                'locales'                  => 'Locales',
                'magic-ai'                 => 'Magic AI',
                'mode'                     => 'Modo oscuro',
                'products'                 => 'Productos',
                'roles'                    => 'Roles',
                'settings'                 => 'Configuraciones',
                'themes'                   => 'Temas',
                'users'                    => 'Usuarios',
                'integrations'             => 'Integraciones',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'No se han seleccionado registros.',
                'must-select-a-mass-action-option' => 'Debes seleccionar una opción de acción masiva.',
                'must-select-a-mass-action'        => 'Debes seleccionar una acción masiva.',
            ],

            'toolbar' => [
                'length-of' => ':length of',
                'of'        => 'de',
                'per-page'  => 'Por página',
                'results'   => ':total Resultados',
                'selected'  => ':total Seleccionados',

                'mass-actions' => [
                    'submit'        => 'Enviar',
                    'select-option' => 'Seleccionar opción',
                    'select-action' => 'Seleccionar acción',
                ],

                'filter' => [
                    'title' => 'Filtrar',
                ],

                'search_by' => [
                    'code'       => 'Buscar por código',
                    'code_or_id' => 'Buscar por código o id',
                ],

                'search' => [
                    'title' => 'Buscar',
                ],
            ],

            'filters' => [
                'select'   => 'Seleccionar',
                'title'    => 'Aplicar filtros',
                'save'     => 'Guardar',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Escribe al menos 2 caracteres...',
                        'no-results'        => 'No se encontraron resultados...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Limpiar todo',
                    'title'     => 'Filtros personalizados',
                ],

                'boolean-options' => [
                    'false' => 'Falso',
                    'true'  => 'Verdadero',
                ],

                'date-options' => [
                    'last-month'        => 'Último mes',
                    'last-six-months'   => 'Últimos 6 meses',
                    'last-three-months' => 'Últimos 3 meses',
                    'this-month'        => 'Este mes',
                    'this-week'         => 'Esta semana',
                    'this-year'         => 'Este año',
                    'today'             => 'Hoy',
                    'yesterday'         => 'Ayer',
                ],
            ],

            'table' => [
                'actions'              => 'Acciones',
                'no-records-available' => 'No hay registros disponibles.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Aceptar',
                'disagree-btn' => 'Rechazar',
                'message'      => '¿Estás seguro de que deseas realizar esta acción?',
                'title'        => '¿Estás seguro?',
            ],

            'delete' => [
                'agree-btn'    => 'Eliminar',
                'disagree-btn' => 'Cancelar',
                'message'      => '¿Estás seguro de que deseas eliminar?',
                'title'        => 'Confirmar eliminación',
            ],

            'history' => [
                'title'           => 'Vista previa del historial',
                'subtitle'        => 'Revisa rápidamente tus actualizaciones y cambios.',
                'close-btn'       => 'Cerrar',
                'version-label'   => 'Versión',
                'date-time-label' => 'Fecha/Hora',
                'user-label'      => 'Usuario',
                'name-label'      => 'Clave',
                'old-value-label' => 'Valor anterior',
                'new-value-label' => 'Nuevo valor',
                'no-history'      => 'No se encontró historial',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Añadir producto seleccionado',
                'empty-info'    => 'No hay productos disponibles para el término de búsqueda.',
                'empty-title'   => 'No se encontraron productos',
                'product-image' => 'Imagen del producto',
                'qty'           => ':qty Disponible',
                'sku'           => 'SKU - :sku',
                'title'         => 'Seleccionar productos',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Añadir imagen',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Generar imagen',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Solo se permiten archivos de imagen (.jpeg, .jpg, .png, ..)',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Aplicar',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Generar',
                    'generating'       => 'Generando...',
                    'hd'               => 'HD',
                    'model'            => 'Modelo',
                    'number-of-images' => 'Número de imágenes',
                    'prompt'           => 'Indicaciones',
                    'quality'          => 'Calidad',
                    'regenerate'       => 'Regenerar',
                    'regenerating'     => 'Regenerando...',
                    'size'             => 'Tamaño',
                    'standard'         => 'Estándar',
                    'title'            => 'Generación de imágenes AI',
                ],

                'placeholders' => [
                    'front'     => 'Frontal',
                    'next'      => 'Siguiente',
                    'size'      => 'Tamaño',
                    'use-cases' => 'Casos de uso',
                    'zoom'      => 'Zoom',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Añadir vídeo',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Solo se permiten archivos de vídeo (.mp4, .mov, .ogg ..)',
            ],

            'files' => [
                'add-file-btn'      => 'Añadir archivo',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'Solo se permiten archivos pdf',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                  => 'Aplicar',
                'generate'               => 'Generar',
                'generated-content'      => 'Contenido generado',
                'generated-content-info' => 'El contenido AI puede ser engañoso. Por favor, revisa el contenido generado antes de aplicarlo.',
                'generating'             => 'Generando...',
                'prompt'                 => 'Indicaciones',
                'title'                  => 'Asistencia AI',
                'model'                  => 'Modelo',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 Descensurado',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],

    'acl' => [
        'addresses'                => 'Adreces',
        'attribute-families'       => 'Famílies d\'atributs',
        'attribute-groups'         => 'Grups d\'atributs',
        'attributes'               => 'Atributs',
        'cancel'                   => 'Cancel·lar',
        'catalog'                  => 'Catàleg',
        'categories'               => 'Categories',
        'channels'                 => 'Cadenes',
        'configure'                => 'Configurar',
        'configuration'            => 'Configuració',
        'copy'                     => 'Copiar',
        'create'                   => 'Crear',
        'currencies'               => 'Monedes',
        'dashboard'                => 'Tauler de control',
        'data-transfer'            => 'Transferència de dades',
        'delete'                   => 'Eliminar',
        'edit'                     => 'Editar',
        'email-templates'          => 'Plantilles d\'emails',
        'events'                   => 'Esdeveniments',
        'groups'                   => 'Grups',
        'import'                   => 'Importar',
        'imports'                  => 'Imports',
        'invoices'                 => 'Factures',
        'locales'                  => 'Locales',
        'magic-ai'                 => 'AI màgic',
        'marketing'                => 'Màrqueting',
        'newsletter-subscriptions' => 'Subministraments de butlletí',
        'note'                     => 'Nota',
        'orders'                   => 'Comandes',
        'products'                 => 'Productes',
        'promotions'               => 'Promocions',
        'refunds'                  => 'Reemborsaments',
        'reporting'                => 'Informes',
        'reviews'                  => 'Reseñas',
        'roles'                    => 'Rols',
        'sales'                    => 'Vendes',
        'search-seo'               => 'Cerca & SEO',
        'search-synonyms'          => 'Sinònims de cerca',
        'search-terms'             => 'Tèrmits de cerca',
        'settings'                 => 'Configuració',
        'shipments'                => 'Enviaments',
        'sitemaps'                 => 'Mapes del lloc',
        'subscribers'              => 'Subscriptors del butlletí',
        'tax-categories'           => 'Categories d\'impostos',
        'tax-rates'                => 'Taxes',
        'taxes'                    => 'Impostos',
        'themes'                   => 'Temes',
        'integration'              => 'Integració',
        'url-rewrites'             => 'Reescripcions URL',
        'users'                    => 'Usuaris',
        'category_fields'          => 'Campaments de categoria',
        'view'                     => 'Vista',
        'execute'                  => 'Executar treball',
        'history'                  => 'Historial',
        'restore'                  => 'Restaurar',
        'integrations'             => 'Integracions',
        'api'                      => 'API',
        'tracker'                  => 'Rastrejador de treball',
        'imports'                  => 'Imports',
        'exports'                  => 'Exports',
    ],

    'errors' => [
        'dashboard' => 'Tauler de control',
        'go-back'   => 'Torna enrere',
        'support'   => 'Si el problema persisteix, contacta\'ns a través de <a href=":link" class=":class">:email</a> per obtenir assistència.',

        '404' => [
            'description' => 'Oops! La pàgina que estàs buscant està de vacances. No hem pogut trobar el que cercaves.',
            'title'       => 'Pàgina 404 No trobada',
        ],

        '401' => [
            'description' => 'Oops! Sembla que no estàs autoritzat a accedir a aquesta pàgina. Necessites credencials vàlides.',
            'title'       => '401 No autoritzat',
            'message'     => 'La autenticació ha fallat per credencials invàlides o token expirat.',
        ],

        '403' => [
            'description' => 'Oops! Aquesta pàgina està prohibida. No tens els permisos necessaris per veure aquest contingut.',
            'title'       => '403 Prohibit',
        ],

        '413' => [
            'description' => 'Oops! Estàs intentant pujar un fitxer que és massa gran. Si vols pujar-lo, actualitza la configuració PHP.',
            'title'       => '413 Contingut massa gran',
        ],

        '419' => [
            'description' => 'Oops! La teva sessió ha caducat. Fes una recàrrega de la pàgina i inicia sessió novament per continuar.',
            'title'       => '419 Sessió caducada',
        ],

        '500' => [
            'description' => 'Oops! Alguna cosa ha sortit malament. Sembla que tenim problemes carregant la pàgina que busques.',
            'title'       => '500 Error de servidor intern',
        ],

        '503' => [
            'description' => 'Oops! Sembla que estem temporalment fora de servei per manteniment. Torna en una estona.',
            'title'       => '503 Servei no disponible',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => 'Descarregar',
        'export'     => 'Exportació ràpida',
        'no-records' => 'Res per exportar',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => 'Aquesta canònica s\'està utilitzant en categories o productes.',
        'slug-reserved'   => 'Aquesta canònica està reservada.',
        'invalid-locale'  => 'Locales no vàlides :locales',
    ],

    'footer' => [
        'copy-right' => 'Potenciat per <a href="https://unopim.com/" target="_blank">UnoPim</a>, Un Projecte Comunitari per <a href="https://webkul.com/" target="_blank">Webkul</a>',
    ],

    'emails' => [
        'dear'   => 'Estimat :admin_name',
        'thanks' => 'Si necessites qualsevol ajuda, contacta\'ns a <a href=":link" style=":style">:email</a>.<br/>Gràcies!',

        'admin' => [
            'forgot-password' => [
                'description'    => 'Rebeu aquest correu electrònic perquè hem rebut una sol·licitud de restabliment de contrasenya per al vostre compte.',
                'greeting'       => 'Contrasenya oblidada!',
                'reset-password' => 'Restableix la contrasenya',
                'subject'        => 'Correu electrònic de restabliment de contrasenya',
            ],
        ],
    ],

    'common' => [
        'yes'     => 'Sí',
        'no'      => 'No',
        'true'    => 'Veritable',
        'false'   => 'Fals',
        'enable'  => 'Activat',
        'disable' => 'Desactivat',
    ],
];
