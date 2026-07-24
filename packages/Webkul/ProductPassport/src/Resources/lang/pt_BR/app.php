<?php

return [
    'type' => [
        'label' => 'Passaporte Digital do Produto',
    ],
    'configuration' => [
        'product_passport' => [
            'title'    => 'Passaporte do Produto',
            'info'     => 'Configurações de publicação do passaporte digital do produto.',
            'settings' => [
                'title'                              => 'Configurações do passaporte do produto',
                'enabled'                            => 'Ativado',
                'auto-publish'                       => 'Publicar automaticamente ao salvar',
                'completeness-threshold'             => 'Limite de completude (%)',
                'operator-name'                      => 'Nome do operador econômico',
                'operator-address'                   => 'Endereço do operador econômico',
                'operator-eu-rep'                    => 'Representante autorizado na UE',
                'support-url'                        => 'URL de suporte',
                'enabled-hint'                       => 'Ative o recurso de Passaporte Digital do Produto para este catálogo. Quando desativado, o painel e a grade de passaportes ficam ocultos.',
                'auto-publish-hint'                  => 'Publique uma versão do passaporte automaticamente sempre que um produto for salvo e atingir o limite de completude. Deixe desativado para publicar manualmente.',
                'completeness-threshold-hint'        => 'Completude mínima do produto, em porcentagem, exigida antes que um passaporte possa ser publicado para um idioma.',
                'completeness-threshold-placeholder' => '80',
                'operator-name-hint'                 => 'Nome legal do fabricante ou operador econômico responsável, exibido em todos os passaportes públicos conforme exigido pelo regulamento ESPR.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address-hint'              => 'Endereço postal registrado do operador econômico, exibido no passaporte público para rastreabilidade.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep-hint'               => 'Nome e contato do representante autorizado na UE, exigido quando o fabricante está estabelecido fora da UE.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url-hint'                   => 'Página pública onde os clientes podem encontrar ajuda ou informações de garantia. Exibida como um link em todos os passaportes.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],
    'groups' => [
        'dpp' => 'Passaporte Digital do Produto',
    ],
    'attributes' => [
        'dpp_material_composition'      => 'Composição do material',
        'dpp_substances_of_concern'     => 'Substâncias de preocupação',
        'dpp_recycled_content_pct'      => 'Conteúdo reciclado (%)',
        'dpp_carbon_footprint'          => 'Pegada de carbono',
        'dpp_energy_consumption'        => 'Consumo de energia',
        'dpp_durability_statement'      => 'Declaração de durabilidade',
        'dpp_repairability_score'       => 'Pontuação de reparabilidade',
        'dpp_spare_parts_availability'  => 'Disponibilidade de peças de reposição',
        'dpp_care_instructions'         => 'Instruções de cuidado',
        'dpp_disassembly_guide'         => 'Guia de desmontagem',
        'dpp_manufacturer_name'         => 'Nome do fabricante',
        'dpp_manufacturing_site'        => 'Local de fabricação',
        'dpp_country_of_origin'         => 'País de origem',
        'dpp_supply_chain_notes'        => 'Notas da cadeia de suprimentos',
        'dpp_end_of_life_instructions'  => 'Instruções de fim de vida',
        'dpp_take_back_scheme'          => 'Programa de devolução',
        'dpp_declaration_of_conformity' => 'Declaração de conformidade',
        'dpp_test_reports'              => 'Relatórios de teste',
        'dpp_certificates'              => 'Certificados',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Identificador do modelo',
        'dpp_batch_identifier'          => 'Identificador do lote',
        'dpp_warranty_terms'            => 'Condições de garantia',
    ],
    'console' => [
        'install-attributes' => [
            'success' => 'Os atributos do Passaporte Digital do Produto foram instalados com sucesso.',
        ],
    ],

    'public' => [
        'badge'         => 'Passaporte Digital de Produto EU',
        'search-locale' => 'Idioma de pesquisa',
        'sections'      => [
            'passport' => 'Passaporte do Produto',
        ],
        'title'      => 'Passaporte Digital do Produto',
        'identifier' => [
            'title'        => 'Identificação',
            'gtin'         => 'GTIN',
            'model'        => 'Modelo',
            'batch'        => 'Lote',
            'not-provided' => 'Não fornecido',
        ],
        'operator' => [
            'title' => 'Operador econômico',
        ],
        'documents' => [
            'title' => 'Documentos',
        ],
    ],

    'publications' => [
        'not-found'      => 'Nenhum passaporte encontrado para o id :id.',
        'index'          => [
            'disabled-notice' => 'A publicação de passaportes está desativada no momento. Os passaportes existentes são exibidos abaixo para gerenciamento (visualizar e retirar).',
            'title'           => 'Passaportes Digitais do Produto',
        ],
        'datagrid' => [
            'uuid'            => 'UUID',
            'sku'             => 'SKU',
            'channel'         => 'Canal',
            'status'          => 'Status',
            'live-locales'    => 'Idiomas ativos',
            'last-published'  => 'Última publicação',
            'withdraw'        => 'Retirar',
        ],
        'publish-queued' => 'A publicação do passaporte foi enfileirada.',
        'withdrawn'      => 'Passaporte retirado com sucesso.',
        'mass-publish'   => [
            'action' => 'Publicar Passaporte Digital do Produto',
            'queued' => 'Publicação do passaporte enfileirada para :count produto(s).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Passaportes',
            'view'     => 'Visualizar',
            'publish'  => 'Publicar',
            'withdraw' => 'Retirar',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passaportes',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'publishing'           => 'Publicando…',
                    'queued'               => 'Na fila',
                    'copy-operator-link'   => 'Copiar link do operador',
                    'copy-authority-link'  => 'Copiar link da autoridade',
                    'link-copied'          => 'Link copiado',
                    'download-qr'          => 'Baixar código QR',
                    'title'                => 'Passaporte Digital do Produto',
                    'publishing-disabled'  => 'A publicação de passaportes está desativada para este canal.',
                    'locale'               => 'Idioma',
                    'version'              => 'Versão',
                    'published-at'         => 'Publicado em',
                    'missing-fields'       => 'Campos ausentes',
                    'not-published'        => 'Não publicado',
                    'unscored'             => 'Não avaliado',
                    'publish'              => 'Publicar',
                    'republish'            => 'Republicar',
                    'publish-all'          => 'Publicar todos os idiomas',
                    'auto-publish-on'      => 'A publicação automática está ativada — os passaportes são publicados automaticamente quando o produto é salvo e atinge o limite de completude. Use os botões para publicar agora.',
                    'auto-publish-off'     => 'Publicação manual — use os botões para publicar o passaporte deste produto para cada idioma.',
                ],
            ],
        ],
    ],
    'validation' => [
        'gtin' => 'O :attribute deve ser um GTIN válido (8, 12, 13 ou 14 dígitos com um dígito verificador correto).',
    ],
    'mapping' => [
        'title'         => 'Mapeamento de campos do passaporte',
        'info'          => 'Obtenha cada campo do passaporte a partir de um atributo que você já mantém. Deixe um campo sem mapeamento para recorrer ao seu atributo de passaporte dedicado.',
        'menu'          => 'Mapeamento de campos',
        'field'         => 'Campo do passaporte',
        'source'        => 'Atributo de origem',
        'select-source' => 'Usar o atributo do passaporte',
        'save-btn'      => 'Salvar mapeamento',
        'type-mismatch' => 'A fonte selecionada não é compatível com o tipo deste campo do passaporte.',
        'saved'         => 'Mapeamento de campos salvo com sucesso.',
    ],

];
