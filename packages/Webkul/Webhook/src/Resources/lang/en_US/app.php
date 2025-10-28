<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name'    => 'Webhooks',
                        'submenu' => [
                            'settings' => [
                                'name'    => 'Settings',
                                'title'   => 'Webhook Settings',
                                'save'    => 'Save',
                                'general' => 'General',
                                'active'  => [
                                    'label' => 'Active Webhook',
                                ],
                                'webhook_url' => [
                                    'label' => 'Webhook URL',
                                ],
                                'success' => 'Webhook settings saved successfully',
                                'history' => [
                                    'name' => 'History',
                                ],
                            ],
                            'logs' => [
                                'name' => 'Logs',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'logs' => [
        'index' => [
            'datagrid' => [
                'id'         => 'Id',
                'sku'        => 'SKU',
                'created_at' => 'Date/Time',
                'user'       => 'User',
                'status'     => 'Status',
                'success'    => 'Success',
                'failed'     => 'Failed',
                'delete'     => 'Delete',
            ],
            'title'          => 'Webhook Logs',
            'delete-success' => 'Webhook logs deleted successfully',
            'delete-failed'  => 'Webhook logs deletion failed unexpectedly',
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Please enable webhook from settings',
        'success'       => 'The product data sent to webhook successfully',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Webhook',
        ],
        'settings' => [
            'index'  => 'Settings',
            'update' => 'Update Settings',
        ],
        'logs' => [
            'index'       => 'Logs',
            'delete'      => 'Delete',
            'mass-delete' => 'Mass Delete',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'title' => 'Webhook Settings',
                ],
            ],

            'logs' => [
                'index' => [
                    'title' => 'Webhook logs',
                ],
            ],
        ],
    ],
];
