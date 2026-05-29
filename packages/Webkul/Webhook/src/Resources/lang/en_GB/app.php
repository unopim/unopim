<?php

declare(strict_types=1);

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Webhook',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Please enable Webhook from settings',
        'success'       => 'The product data sent to Webhook successfully',
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
                    'name'    => 'Settings',
                    'title'   => 'Webhook Settings',
                    'save'    => 'Save',
                    'general' => 'General',
                    'active'  => [
                        'label' => 'Active Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Webhook URL',
                        'required'          => 'A Webhook URL is required when the webhook is active.',
                        'scheme'            => 'The Webhook URL must start with http:// or https://.',
                        'connection_failed' => 'The Webhook URL could not be reached. Please check the URL.',
                        'unreachable'       => 'The Webhook URL is not valid (HTTP :code).',
                    ],
                    'success'    => 'Webhook settings saved successfully',
                    'logs-title' => 'Logs',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Date/Time',
                        'user'             => 'User',
                        'status'           => 'Status',
                        'success'          => 'Success',
                        'failed'           => 'Failed',
                        'server_error'     => 'Server Error',
                        'timeout_or_error' => 'Timeout/Error',
                        'delete'           => 'Delete',
                    ],
                    'title'          => 'Webhook Logs',
                    'delete-success' => 'Webhook logs deleted successfully',
                    'delete-failed'  => 'Webhook logs deletion failed unexpectedly',
                ],
            ],
        ],
    ],
];
