<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhooks',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Por favor, ative o Webhook nas definições',
        'success'       => 'Os dados do produto foram enviados para o Webhook com sucesso',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],
        'settings' => [
            'index'  => 'Definições',
            'update' => 'Atualizar definições',
        ],
        'logs' => [
            'index'       => 'Registos',
            'view'        => 'Ver',
            'delete'      => 'Eliminar',
            'mass-delete' => 'Eliminação em massa',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Produto criado',
            'updated' => 'Produto atualizado',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Criar Webhook',
            'logs-btn'     => 'Registos',
            'back-btn'     => 'Voltar aos Webhooks',
            'default-name' => 'Predefinido',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Nome',
                'url'        => 'URL',
                'events'     => 'Eventos',
                'status'     => 'Estado',
                'active'     => 'Ativo',
                'inactive'   => 'Inativo',
                'created_at' => 'Criado em',
                'edit'       => 'Editar',
                'delete'     => 'Eliminar',
            ],
        ],
        'create' => [
            'title'    => 'Criar Webhook',
            'cancel'   => 'Cancelar',
            'save-btn' => 'Guardar',
        ],
        'edit' => [
            'title'    => 'Editar Webhook',
            'cancel'   => 'Cancelar',
            'save-btn' => 'Guardar',
        ],
        'form' => [
            'general'       => 'Geral',
            'name'          => 'Nome',
            'url'           => 'URL',
            'events'        => 'Eventos',
            'select-events' => 'Selecionar eventos',
            'secret'        => 'Segredo de assinatura',
            'secret-set'    => 'Já existe um segredo definido',
            'secret-hint'   => 'Utilizado para assinar cada payload com uma assinatura HMAC SHA-256. Deixe em branco para manter o segredo atual.',
            'settings'      => 'Definições',
            'active'        => 'Ativo',
            'test'          => 'Testar ligação',
            'test-hint'     => 'Envie um pedido de teste para o URL acima.',
            'test-btn'      => 'Enviar teste',
            'test-no-url'   => 'Por favor, introduza primeiro um URL.',
            'test-failed'   => 'O pedido de teste falhou.',
            'headers'       => 'Cabeçalhos personalizados',
            'add-header'    => 'Adicionar cabeçalho',
            'no-headers'    => 'Nenhum cabeçalho personalizado adicionado.',
            'header-key'    => 'Cabeçalho',
            'header-value'  => 'Valor',
        ],
        'create-success' => 'Webhook criado com sucesso',
        'update-success' => 'Webhook atualizado com sucesso',
        'delete-success' => 'Webhook eliminado com sucesso',
        'delete-failed'  => 'Falha ao eliminar o Webhook',
        'validation'     => [
            'unsafe-url' => 'O URL aponta para um endereço privado, de loopback ou interno e não é permitido.',
            'scheme'     => 'O URL deve começar com http:// ou https://.',
        ],
        'test' => [
            'payload-message'   => 'Pedido de teste do webhook Unopim',
            'connection-failed' => 'Não foi possível aceder ao URL. Verifique o URL.',
            'unreachable'       => 'O URL não está acessível (HTTP :code).',
            'reachable'         => 'O URL está acessível.',
        ],
        'prune' => [
            'disabled' => 'A retenção de registos do webhook está desativada; nada foi removido.',
            'done'     => ':count registo(s) de webhook mais antigo(s) que :days dia(s) removido(s).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Definições',
                    'save'    => 'Guardar',
                    'general' => 'Geral',
                    'active'  => [
                        'label' => 'Webhook ativo',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL do Webhook',
                        'required'          => 'É necessário um URL do Webhook quando o Webhook está ativo.',
                        'scheme'            => 'O URL do Webhook deve começar com http:// ou https://.',
                        'connection_failed' => 'Não foi possível aceder ao URL do Webhook. Verifique o URL.',
                        'unreachable'       => 'O URL do Webhook não é válido (HTTP :code).',
                        'unsafe'            => 'O URL do webhook aponta para um endereço privado, de loopback ou interno e não é permitido.',
                    ],
                    'success'    => 'Definições do Webhook guardadas com sucesso',
                    'title'      => 'Definições do Webhook',
                    'logs-title' => 'Registos',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Evento',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Utilizador',
                        'status'           => 'Estado',
                        'success'          => 'Sucesso',
                        'failed'           => 'Falhado',
                        'server_error'     => 'Erro do servidor',
                        'timeout_or_error' => 'Tempo esgotado/Erro',
                        'delete'           => 'Eliminar',
                        'view'             => 'Ver',
                    ],
                    'title'          => 'Registos do Webhook',
                    'show-title'     => 'Detalhes do Registo do Webhook',
                    'sent-payload'   => 'Payload Enviado',
                    'response'       => 'Resposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Nenhum payload registado para este registo.',
                    'load-failed'    => 'Falha ao carregar os detalhes do registo.',
                    'delete-success' => 'Registos do Webhook eliminados com sucesso',
                    'delete-failed'  => 'A eliminação dos registos do Webhook falhou inesperadamente',
                    'unauthorized'   => 'Esta ação não está autorizada',
                ],
            ],
        ],
    ],
];
