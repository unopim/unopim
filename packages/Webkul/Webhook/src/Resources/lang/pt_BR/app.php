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
        'delete-failed' => 'Por favor, ative o Webhook nas configurações',
        'success'       => 'Os dados do produto foram enviados ao Webhook com sucesso',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Excluir',
        ],
        'logs' => [
            'index'       => 'Registros',
            'view'        => 'Ver',
            'delete'      => 'Excluir',
            'mass-delete' => 'Exclusão em massa',
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
            'logs-btn'     => 'Registros',
            'back-btn'     => 'Voltar aos Webhooks',
            'default-name' => 'Padrão',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Nome',
                'url'        => 'URL',
                'events'     => 'Eventos',
                'status'     => 'Status',
                'active'     => 'Ativo',
                'inactive'   => 'Inativo',
                'created_at' => 'Criado em',
                'edit'       => 'Editar',
                'delete'     => 'Excluir',
            ],
        ],
        'create' => [
            'title'    => 'Criar Webhook',
            'save-btn' => 'Salvar',
        ],
        'edit' => [
            'title'    => 'Editar Webhook',
            'save-btn' => 'Salvar',
        ],
        'form' => [
            'general'       => 'Geral',
            'name'          => 'Nome',
            'url'           => 'URL',
            'events'        => 'Eventos',
            'select-events' => 'Selecionar eventos',
            'secret'        => 'Segredo de assinatura',
            'secret-set'    => 'Um segredo já está definido',
            'secret-hint'   => 'Usado para assinar cada payload com uma assinatura HMAC SHA-256. Deixe em branco para manter o segredo atual.',
            'settings'      => 'Configurações',
            'active'        => 'Ativo',
            'test'          => 'Testar conexão',
            'test-hint'     => 'Envie uma requisição de teste para a URL acima.',
            'test-btn'      => 'Enviar teste',
            'test-no-url'   => 'Por favor, insira uma URL primeiro.',
            'test-failed'   => 'A requisição de teste falhou.',
            'headers'       => 'Cabeçalhos personalizados',
            'add-header'    => 'Adicionar cabeçalho',
            'no-headers'    => 'Nenhum cabeçalho personalizado adicionado.',
            'header-key'    => 'Cabeçalho',
            'header-value'  => 'Valor',
        ],
        'create-success' => 'Webhook criado com sucesso',
        'update-success' => 'Webhook atualizado com sucesso',
        'delete-success' => 'Webhook excluído com sucesso',
        'delete-failed'  => 'Falha ao excluir o Webhook',
        'validation'     => [
            'unsafe-url' => 'A URL aponta para um endereço privado, de loopback ou interno e não é permitida.',
            'scheme'     => 'A URL deve começar com http:// ou https://.',
        ],
        'test' => [
            'payload-message'   => 'Requisição de teste do webhook Unopim',
            'connection-failed' => 'Não foi possível acessar a URL. Verifique a URL.',
            'unreachable'       => 'A URL não está acessível (HTTP :code).',
            'reachable'         => 'A URL está acessível.',
        ],
        'prune' => [
            'disabled' => 'A retenção de registros do webhook está desativada; nada foi removido.',
            'done'     => ':count registro(s) de webhook mais antigo(s) que :days dia(s) removido(s).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Evento',
                        'created_at'       => 'Data/Hora',
                        'user'             => 'Usuário',
                        'status'           => 'Status',
                        'success'          => 'Sucesso',
                        'failed'           => 'Falhou',
                        'server_error'     => 'Erro do servidor',
                        'timeout_or_error' => 'Tempo esgotado/Erro',
                        'delete'           => 'Excluir',
                        'view'             => 'Ver',
                    ],
                    'title'          => 'Registros do Webhook',
                    'show-title'     => 'Detalhes do Log do Webhook',
                    'sent-payload'   => 'Payload Enviado',
                    'response'       => 'Resposta',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Nenhum payload registrado para este log.',
                    'load-failed'    => 'Falha ao carregar os detalhes do log.',
                    'delete-success' => 'Registros do Webhook excluídos com sucesso',
                    'delete-failed'  => 'A exclusão dos registros do Webhook falhou inesperadamente',
                    'unauthorized'   => 'Esta ação não está autorizada',
                ],
            ],
        ],
    ],
];
