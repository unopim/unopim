<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => [
                'default' => 'Padrão',
            ],

            'attribute-groups' => [
                'description'      => 'Descrição',
                'general'          => 'Em geral',
                'inventories'      => 'Inventários',
                'meta-description' => 'Meta descrição',
                'price'            => 'Preço',
                'technical'        => 'Técnico',
                'shipping'         => 'Envio',
            ],

            'attributes' => [
                'brand'                => 'Marca',
                'color'                => 'Cor',
                'cost'                 => 'Custo',
                'description'          => 'Descrição',
                'featured'             => 'Apresentou',
                'guest-checkout'       => 'Check-out de convidado',
                'height'               => 'Altura',
                'length'               => 'Comprimento',
                'manage-stock'         => 'Gerenciar estoque',
                'meta-description'     => 'Meta descrição',
                'meta-keywords'        => 'Meta palavras-chave',
                'meta-title'           => 'Metatítulo',
                'name'                 => 'Nome',
                'new'                  => 'Novo',
                'price'                => 'Preço',
                'product-number'       => 'Número do produto',
                'short-description'    => 'Breve descrição',
                'size'                 => 'Tamanho',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Preço especial de',
                'special-price-to'     => 'Preço especial para',
                'special-price'        => 'Preço especial',
                'status'               => 'Status',
                'tax-category'         => 'Categoria fiscal',
                'url-key'              => 'Chave de URL',
                'visible-individually' => 'Visível individualmente',
                'weight'               => 'Peso',
                'width'                => 'Largura',
            ],

            'attribute-options' => [
                'black'  => 'Preto',
                'green'  => 'Verde',
                'l'      => 'eu',
                'm'      => 'M',
                'red'    => 'Vermelho',
                's'      => 'S',
                'white'  => 'Branco',
                'xl'     => 'XL',
                'yellow' => 'Amarelo',
            ],
        ],

        'category' => [
            'categories' => [
                'description' => 'Descrição da categoria raiz',
                'name'        => 'Raiz',
            ],

            'category_fields' => [
                'name'        => 'Nome',
                'description' => 'Descrição',
            ],
        ],

        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Sobre nós Conteúdo da página',
                    'title'   => 'Sobre nós',
                ],

                'contact-us' => [
                    'content' => 'Fale conosco Conteúdo da página',
                    'title'   => 'Contate-nos',
                ],

                'customer-service' => [
                    'content' => 'Conteúdo da página de atendimento ao cliente',
                    'title'   => 'Atendimento ao Cliente',
                ],

                'payment-policy' => [
                    'content' => 'Conteúdo da página de política de pagamento',
                    'title'   => 'Política de Pagamento',
                ],

                'privacy-policy' => [
                    'content' => 'Conteúdo da página da política de privacidade',
                    'title'   => 'política de Privacidade',
                ],

                'refund-policy' => [
                    'content' => 'Conteúdo da página da política de reembolso',
                    'title'   => 'Política de Reembolso',
                ],

                'return-policy' => [
                    'content' => 'Conteúdo da página da política de devolução',
                    'title'   => 'Política de devolução',
                ],

                'shipping-policy' => [
                    'content' => 'Conteúdo da página de política de envio',
                    'title'   => 'Política de Envio',
                ],

                'terms-conditions' => [
                    'content' => 'Termos e Condições Conteúdo da página',
                    'title'   => 'Termos e Condições',
                ],

                'terms-of-use' => [
                    'content' => 'Termos de uso Conteúdo da página',
                    'title'   => 'Termos de Uso',
                ],

                'whats-new' => [
                    'content' => 'Conteúdo da página Novidades',
                    'title'   => 'O que há de novo',
                ],
            ],
        ],

        'core' => [
            'channels' => [
                'meta-title'       => 'Loja de demonstração',
                'meta-keywords'    => 'Meta palavra-chave da loja de demonstração',
                'meta-description' => 'Meta descrição da loja de demonstração',
                'name'             => 'Padrão',
            ],

            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Shekel israelense',
                'CNY' => 'Yuan chinês',
                'EUR' => 'EURO',
                'GBP' => 'Libra esterlina',
                'INR' => 'Rupia Indiana',
                'IRR' => 'Rial iraniano',
                'JPY' => 'Iene japonês',
                'RUB' => 'Rublo Russo',
                'SAR' => 'Rial saudita',
                'TRY' => 'Lira Turca',
                'UAH' => 'Hryvnia ucraniana',
                'USD' => 'Dólar americano',
            ],
        ],

        'customer' => [
            'customer-groups' => [
                'general'   => 'Em geral',
                'guest'     => 'Convidado',
                'wholesale' => 'Atacado',
            ],
        ],

        'inventory' => [
            'inventory-sources' => [
                'name' => 'Padrão',
            ],
        ],

        'shop' => [
            'theme-customizations' => [
                'all-products' => [
                    'name' => 'Todos os produtos',

                    'options' => [
                        'title' => 'Todos os produtos',
                    ],
                ],

                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Ver tudo',
                        'description' => 'Apresentando nossas novas coleções ousadas! Eleve o seu estilo com designs ousados ​​e declarações vibrantes. Explore padrões marcantes e cores ousadas que redefinem seu guarda-roupa. Prepare-se para abraçar o extraordinário!',
                        'title'       => 'Prepare-se para nossas novas coleções ousadas!',
                    ],

                    'name' => 'Coleções Ousadas',
                ],

                'categories-collections' => [
                    'name' => 'Categorias Coleções',
                ],

                'featured-collections' => [
                    'name' => 'Coleções em destaque',

                    'options' => [
                        'title' => 'Produtos em destaque',
                    ],
                ],

                'footer-links' => [
                    'name' => 'Links de rodapé',

                    'options' => [
                        'about-us'         => 'Sobre nós',
                        'contact-us'       => 'Contate-nos',
                        'customer-service' => 'Atendimento ao Cliente',
                        'payment-policy'   => 'Política de Pagamento',
                        'privacy-policy'   => 'política de Privacidade',
                        'refund-policy'    => 'Política de Reembolso',
                        'return-policy'    => 'Política de devolução',
                        'shipping-policy'  => 'Política de Envio',
                        'terms-conditions' => 'Termos e Condições',
                        'terms-of-use'     => 'Termos de Uso',
                        'whats-new'        => 'O que há de novo',
                    ],
                ],

                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Nossas coleções',
                        'sub-title-2' => 'Nossas coleções',
                        'title'       => 'O jogo com nossas novas adições!',
                    ],

                    'name' => 'Contêiner de jogo',
                ],

                'image-carousel' => [
                    'name' => 'Carrossel de imagens',

                    'sliders' => [
                        'title' => 'Prepare-se para a nova coleção',
                    ],
                ],

                'new-products' => [
                    'name' => 'Novos produtos',

                    'options' => [
                        'title' => 'Novos produtos',
                    ],
                ],

                'offer-information' => [
                    'content' => [
                        'title' => 'Ganhe ATÉ 40% DE DESCONTO no seu primeiro pedido COMPRE AGORA',
                    ],

                    'name' => 'Informações da oferta',
                ],

                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'EMI gratuito disponível em todos os principais cartões de crédito',
                        'free-shipping-info'   => 'Aproveite frete grátis em todos os pedidos',
                        'product-replace-info' => 'Fácil substituição do produto disponível!',
                        'time-support-info'    => 'Suporte dedicado 24 horas por dia, 7 dias por semana, via chat e e-mail',
                    ],

                    'name' => 'Conteúdo de serviços',

                    'title' => [
                        'emi-available'   => 'Emi disponível',
                        'free-shipping'   => 'Frete grátis',
                        'product-replace' => 'Substituição do produto',
                        'time-support'    => 'Suporte 24 horas por dia, 7 dias por semana',
                    ],
                ],

                'top-collections' => [
                    'content' => [
                        'sub-title-1' => 'Nossas coleções',
                        'sub-title-2' => 'Nossas coleções',
                        'sub-title-3' => 'Nossas coleções',
                        'sub-title-4' => 'Nossas coleções',
                        'sub-title-5' => 'Nossas coleções',
                        'sub-title-6' => 'Nossas coleções',
                        'title'       => 'O jogo com nossas novas adições!',
                    ],

                    'name' => 'Principais coleções',
                ],
            ],
        ],

        'user' => [
            'roles' => [
                'description' => 'Os usuários desta função terão todo o acesso',
                'name'        => 'Administrador',
            ],

            'users' => [
                'name' => 'Exemplo',
            ],
        ],
    ],

    'installer' => [
        'index' => [
            'create-administrator' => [
                'admin'            => 'Administrador',
                'unopim'           => 'UnoPim',
                'confirm-password' => 'Confirme sua senha',
                'email-address'    => 'admin@exemplo.com',
                'email'            => 'E-mail',
                'password'         => 'Senha',
                'title'            => 'Criar administrador',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Moedas permitidas',
                'allowed-locales'     => 'Locais permitidos',
                'application-name'    => 'Nome do aplicativo',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Yuan Chinês (CNY)',
                'database-connection' => 'Conexão de banco de dados',
                'database-hostname'   => 'Nome do host do banco de dados',
                'database-name'       => 'Nome do banco de dados',
                'database-password'   => 'Senha do banco de dados',
                'database-port'       => 'Porta do banco de dados',
                'database-prefix'     => 'Prefixo do banco de dados',
                'database-username'   => 'Nome de usuário do banco de dados',
                'default-currency'    => 'Moeda padrão',
                'default-locale'      => 'Localidade padrão',
                'default-timezone'    => 'Fuso horário padrão',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL padrão',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Rial iraniano (IRR)',
                'israeli'             => 'Shekel israelense (AFN)',
                'japanese-yen'        => 'Iene Japonês (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Libra Esterlina (GBP)',
                'rupee'               => 'Rupia Indiana (INR)',
                'russian-ruble'       => 'Rublo Russo (RUB)',
                'saudi'               => 'Rial Saudita (SAR)',
                'select-timezone'     => 'Selecione o fuso horário',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Configuração do banco de dados',
                'turkish-lira'        => 'Lira Turca (TRY)',
                'ukrainian-hryvnia'   => 'Hryvnia Ucraniana (UAH)',
                'usd'                 => 'Dólar americano (USD)',
                'warning-message'     => 'Cuidado! As configurações dos idiomas padrão do sistema, bem como a moeda padrão, são permanentes e não podem ser alteradas novamente.',
            ],

            'installation-processing' => [
                'unopim'      => 'Instalação UnoPim',
                'unopim-info' => 'Criando as tabelas do banco de dados, isso pode levar alguns instantes',
                'title'       => 'Instalação',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Painel de administração',
                'unopim-forums'             => 'Fórum UnoPim',
                'explore-unopim-extensions' => 'Explore a extensão UnoPim',
                'title-info'                => 'UnoPim foi instalado com sucesso em seu sistema.',
                'title'                     => 'Instalação concluída',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Crie a tabela do banco de dados',
                'install-info-button'     => 'Clique no botão abaixo para',
                'install-info'            => 'UnoPim para instalação',
                'install'                 => 'Instalação',
                'populate-database-table' => 'Preencher as tabelas do banco de dados',
                'start-installation'      => 'Iniciar instalação',
                'title'                   => 'Pronto para instalação',
            ],

            'start' => [
                'locale'        => 'Localidade',
                'main'          => 'Começar',
                'select-locale' => 'Selecione o local',
                'title'         => 'Sua instalação do UnoPim',
                'welcome-title' => 'Bem vindo ao UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Calendário',
                'ctype'       => 'cTipo',
                'curl'        => 'curvatura',
                'dom'         => 'dom',
                'fileinfo'    => 'arquivoInfo',
                'filter'      => 'Filtro',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'internacional',
                'json'        => 'JSON',
                'mbstring'    => 'mbstring',
                'openssl'     => 'abressl',
                'pcre'        => 'pcre',
                'pdo'         => 'DOP',
                'php-version' => '8.2 ou superior',
                'php'         => 'PHP',
                'session'     => 'sessão',
                'title'       => 'Requisitos do sistema',
                'tokenizer'   => 'tokenizador',
                'xml'         => 'XML',
            ],

            'back'                     => 'Voltar',
            'unopim-info'              => 'um projeto comunitário de',
            'unopim-logo'              => 'Logo',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Continuar',
            'installation-description' => 'A instalação do UnoPim normalmente envolve várias etapas. Aqui está um resumo geral do processo de instalação do UnoPim:',
            'wizard-language'          => 'Idioma do assistente de instalação',
            'installation-info'        => 'Estamos felizes em ver você aqui!',
            'installation-title'       => 'Bem-vindo à instalação',
            'save-configuration'       => 'Salvar configuração',
            'skip'                     => 'Pular',
            'title'                    => 'Instalador UnoPim',
            'webkul'                   => 'Webkul',
        ],
    ],
];
