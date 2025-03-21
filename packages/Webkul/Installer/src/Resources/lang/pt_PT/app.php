<?php

return [
    'seeders' => [
        'attribute' => [
            'attribute-families' => 'Padrão',
            'attribute-groups'   => [
                'description'      => 'Descrição',
                'general'          => 'Geral',
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
                'featured'             => 'Destaque',
                'guest-checkout'       => 'Checkout como convidado',
                'height'               => 'Altura',
                'length'               => 'Comprimento',
                'manage-stock'         => 'Gerenciar estoque',
                'meta-description'     => 'Meta descrição',
                'meta-keywords'        => 'Meta palavras-chave',
                'meta-title'           => 'Meta título',
                'name'                 => 'Nome',
                'new'                  => 'Novo',
                'price'                => 'Preço',
                'product-number'       => 'Número do produto',
                'short-description'    => 'Descrição curta',
                'size'                 => 'Tamanho',
                'sku'                  => 'SKU',
                'special-price-from'   => 'Preço especial de',
                'special-price-to'     => 'Preço especial até',
                'special-price'        => 'Preço especial',
                'status'               => 'Status',
                'tax-category'         => 'Categoria de imposto',
                'url-key'              => 'Chave da URL',
                'visible-individually' => 'Visível individualmente',
                'weight'               => 'Peso',
                'width'                => 'Largura',
            ],
            'attribute-options' => [
                'black'  => 'Preto',
                'green'  => 'Verde',
                'l'      => 'L',
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
                'description' => 'Descrição da categoria principal',
                'name'        => 'Categoria principal',
            ],
            'category_fields' => [
                'name'        => 'Nome',
                'description' => 'Descrição',
            ],
        ],
        'cms' => [
            'pages' => [
                'about-us' => [
                    'content' => 'Conteúdo da página sobre nós',
                    'title'   => 'Sobre nós',
                ],
                'contact-us' => [
                    'content' => 'Conteúdo da página de contato',
                    'title'   => 'Contato',
                ],
                'customer-service' => [
                    'content' => 'Conteúdo da página de serviço ao cliente',
                    'title'   => 'Serviço ao cliente',
                ],
                'payment-policy' => [
                    'content' => 'Conteúdo da página de política de pagamento',
                    'title'   => 'Política de pagamento',
                ],
                'privacy-policy' => [
                    'content' => 'Conteúdo da página de política de privacidade',
                    'title'   => 'Política de privacidade',
                ],
                'refund-policy' => [
                    'content' => 'Conteúdo da página de política de reembolso',
                    'title'   => 'Política de reembolso',
                ],
                'return-policy' => [
                    'content' => 'Conteúdo da página de política de devolução',
                    'title'   => 'Política de devolução',
                ],
                'shipping-policy' => [
                    'content' => 'Conteúdo da página de política de envio',
                    'title'   => 'Política de envio',
                ],
                'terms-conditions' => [
                    'content' => 'Conteúdo da página de termos e condições',
                    'title'   => 'Termos e condições',
                ],
                'terms-of-use' => [
                    'content' => 'Conteúdo da página de termos de uso',
                    'title'   => 'Termos de uso',
                ],
                'whats-new' => [
                    'content' => 'Conteúdo da página de novidades',
                    'title'   => 'Novidades',
                ],
            ],
        ],
        'core' => [
            'channels' => [
                'meta-title'       => 'Loja demo',
                'meta-keywords'    => 'Palavras-chave meta loja demo',
                'meta-description' => 'Descrição meta loja demo',
                'name'             => 'Padrão',
            ],
            'currencies' => [
                'AED' => 'Dirham',
                'AFN' => 'Árabe Afegão',
                'CNY' => 'Yuan Chinês',
                'EUR' => 'Euro',
                'GBP' => 'Libra Esterlina',
                'INR' => 'Rupia Indiana',
                'IRR' => 'Rial Iraniano',
                'JPY' => 'Iene Japonês',
                'RUB' => 'Rublo Russo',
                'SAR' => 'Rial Saudita',
                'TRY' => 'Lira Turca',
                'UAH' => 'Hryvnia Ucraniana',
                'USD' => 'Dólar Americano',
            ],
        ],
        'customer' => [
            'customer-groups' => [
                'general'   => 'Geral',
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
                    'name'    => 'Todos os produtos',
                    'options' => [
                        'title' => 'Todos os produtos',
                    ],
                ],
                'bold-collections' => [
                    'content' => [
                        'btn-title'   => 'Ver todos',
                        'description' => 'Apresentamos a nova coleção Bold! Eleve o seu estilo com designs ousados e cores vibrantes. Defina seu visual de maneira totalmente nova com padrões ousados e tons chamativos. Prepare-se para momentos especiais com nossa coleção Bold!',
                        'title'       => 'Conheça a nova coleção Bold!',
                    ],
                    'name' => 'Coleção Bold',
                ],
                'categories-collections' => [
                    'name' => 'Coleções de categorias',
                ],
                'featured-collections' => [
                    'name'    => 'Coleções em destaque',
                    'options' => [
                        'title' => 'Produtos em destaque',
                    ],
                ],
                'footer-links' => [
                    'name'    => 'Links do rodapé',
                    'options' => [
                        'about-us'         => 'Sobre nós',
                        'contact-us'       => 'Contato',
                        'customer-service' => 'Serviço ao cliente',
                        'payment-policy'   => 'Política de pagamento',
                        'privacy-policy'   => 'Política de privacidade',
                        'refund-policy'    => 'Política de reembolso',
                        'return-policy'    => 'Política de devolução',
                        'shipping-policy'  => 'Política de envio',
                        'terms-conditions' => 'Termos e condições',
                        'terms-of-use'     => 'Termos de uso',
                        'whats-new'        => 'Novidades',
                    ],
                ],
                'game-container' => [
                    'content' => [
                        'sub-title-1' => 'Nossas coleções',
                        'sub-title-2' => 'Nossas coleções',
                        'title'       => 'Prepare-se para um jogo com nossos novos lançamentos!',
                    ],
                    'name' => 'Container de jogo',
                ],
                'image-carousel' => [
                    'name'    => 'Carrossel de imagens',
                    'sliders' => [
                        'title' => 'Prepare-se para conhecer a nova coleção',
                    ],
                ],
                'new-products' => [
                    'name'    => 'Novos produtos',
                    'options' => [
                        'title' => 'Novos produtos',
                    ],
                ],
                'offer-information' => [
                    'content' => [
                        'title' => 'Até 40% de desconto nos primeiros pedidos! COMPRE AGORA',
                    ],
                    'name' => 'Informações da oferta',
                ],
                'services-content' => [
                    'description' => [
                        'emi-available-info'   => 'EMI disponível em todos os principais cartões de crédito',
                        'free-shipping-info'   => 'Aproveite o envio gratuito para todos os pedidos',
                        'product-replace-info' => 'Trocas fáceis disponíveis!',
                        'time-support-info'    => 'Suporte dedicado 24/7 via chat e e-mail',
                    ],
                    'name'  => 'Conteúdo dos serviços',
                    'title' => [
                        'emi-available'   => 'EMI disponível',
                        'free-shipping'   => 'Envio gratuito',
                        'product-replace' => 'Troca de produto',
                        'time-support'    => 'Suporte 24/7',
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
                        'title'       => 'Prepare-se para um jogo com nossos novos lançamentos!',
                    ],
                    'name' => 'Principais coleções',
                ],
            ],
        ],
        'user' => [
            'roles' => [
                'description' => 'Este papel terá todos os acessos',
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
                'confirm-password' => 'Confirmar Palavra-passe',
                'email-address'    => 'admin@example.com',
                'email'            => 'E-mail',
                'password'         => 'Palavra-passe',
                'title'            => 'Criar Administrador',
            ],

            'environment-configuration' => [
                'allowed-currencies'  => 'Moedas Permitidas',
                'allowed-locales'     => 'Locais Permitidos',
                'application-name'    => 'Nome da Aplicação',
                'unopim'              => 'UnoPim',
                'chinese-yuan'        => 'Yuan Chinês (CNY)',
                'database-connection' => 'Conexão de Base de Dados',
                'database-hostname'   => 'Nome do Host da Base de Dados',
                'database-name'       => 'Nome da Base de Dados',
                'database-password'   => 'Palavra-passe da Base de Dados',
                'database-port'       => 'Porta da Base de Dados',
                'database-prefix'     => 'Prefixo da Base de Dados',
                'database-username'   => 'Utilizador da Base de Dados',
                'default-currency'    => 'Moeda Padrão',
                'default-locale'      => 'Local Padrão',
                'default-timezone'    => 'Fuso Horário Padrão',
                'default-url-link'    => 'https://localhost',
                'default-url'         => 'URL Padrão',
                'dirham'              => 'Dirham (AED)',
                'euro'                => 'Euro (EUR)',
                'iranian'             => 'Rial Iraniano (IRR)',
                'israeli'             => 'Shekel Israelita (ILS)',
                'japanese-yen'        => 'Iene Japonês (JPY)',
                'mysql'               => 'MySQL',
                'pgsql'               => 'pgSQL',
                'pound'               => 'Libra Esterlina (GBP)',
                'rupee'               => 'Rúpia Indiana (INR)',
                'russian-ruble'       => 'Rublo Russo (RUB)',
                'saudi'               => 'Riyal Saudita (SAR)',
                'select-timezone'     => 'Selecione o Fuso Horário',
                'sqlsrv'              => 'SQLSRV',
                'title'               => 'Configuração de Base de Dados',
                'turkish-lira'        => 'Lira Turca (TRY)',
                'ukrainian-hryvnia'   => 'Hryvnia Ucraniana (UAH)',
                'usd'                 => 'Dólar Americano (USD)',
                'warning-message'     => 'Atenção! O local e a moeda padrão não podem ser alterados mais tarde.',
            ],

            'installation-processing' => [
                'unopim'      => 'A Instalar o UnoPim',
                'unopim-info' => 'A criar tabelas na base de dados, este processo pode demorar alguns minutos.',
                'title'       => 'Processo de Instalação',
            ],

            'installation-completed' => [
                'admin-panel'               => 'Painel de Administração',
                'unopim-forums'             => 'Fóruns UnoPim',
                'explore-unopim-extensions' => 'Explorar Extensões UnoPim',
                'title-info'                => 'UnoPim foi instalado com sucesso.',
                'title'                     => 'Instalação Concluída',
            ],

            'ready-for-installation' => [
                'create-databsae-table'   => 'Criar tabelas da base de dados',
                'install-info-button'     => 'Clique no botão abaixo para começar',
                'install-info'            => 'a instalação do UnoPim',
                'install'                 => 'Instalar',
                'populate-database-table' => 'Popular tabelas da base de dados',
                'start-installation'      => 'Iniciar Instalação',
                'title'                   => 'Pronto para Instalar',
            ],

            'start' => [
                'locale'        => 'Local',
                'main'          => 'Início',
                'select-locale' => 'Selecione o Idioma',
                'title'         => 'Instalar UnoPim',
                'welcome-title' => 'Bem-vindo ao UnoPim :version',
            ],

            'server-requirements' => [
                'calendar'    => 'Calendário',
                'ctype'       => 'cType',
                'curl'        => 'cURL',
                'dom'         => 'DOM',
                'fileinfo'    => 'Informações de Ficheiros',
                'filter'      => 'Filtro',
                'gd'          => 'GD',
                'hash'        => 'Hash',
                'intl'        => 'Internacionalização',
                'json'        => 'JSON',
                'mbstring'    => 'MBString',
                'openssl'     => 'OpenSSL',
                'pcre'        => 'PCRE',
                'pdo'         => 'PDO',
                'php-version' => '8.2 ou superior',
                'php'         => 'PHP',
                'session'     => 'Sessão',
                'title'       => 'Requisitos do Sistema',
                'tokenizer'   => 'Tokenizer',
                'xml'         => 'XML',
            ],

            'back'                     => 'Voltar',
            'unopim-info'              => 'Projeto Comunitário',
            'unopim-logo'              => 'Logo UnoPim',
            'unopim'                   => 'UnoPim',
            'continue'                 => 'Continuar',
            'installation-description' => 'A instalação do UnoPim consiste em várias etapas. Aqui está uma visão geral:',
            'wizard-language'          => 'Idioma do Assistente de Instalação',
            'installation-info'        => 'Obrigado por estar aqui!',
            'installation-title'       => 'Bem-vindo à Instalação',
            'save-configuration'       => 'Guardar Configuração',
            'skip'                     => 'Ignorar',
            'title'                    => 'Assistente de Instalação UnoPim',
            'webkul'                   => 'Webkul',
        ],
    ],
];
