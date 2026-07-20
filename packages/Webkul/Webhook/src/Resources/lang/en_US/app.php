<?php

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
            'view'        => 'View',
            'delete'      => 'Delete',
            'mass-delete' => 'Mass Delete',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Settings',
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
                        'unsafe'            => 'The Webhook URL points at a private, loopback or internal address and is not allowed.',
                    ],
                    'success'    => 'Webhook settings saved successfully',
                    'title'      => 'Webhook Settings',
                    'logs-title' => 'Logs',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'Id',
                        'sku'              => 'SKU',
                        'created_at'       => 'Date/Time',
                        'user'             => 'User',
                        'status'           => 'Status',
                        'success'          => 'Success',
                        'failed'           => 'Failed',
                        'server_error'     => 'Server Error',
                        'timeout_or_error' => 'Timeout/Error',
                        'delete'           => 'Delete',
                        'view'             => 'View',
                    ],
                    'title'          => 'Webhook Logs',
                    'show-title'     => 'Webhook Log Details',
                    'sent-payload'   => 'Sent Payload',
                    'response'       => 'Response',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'No payload recorded for this log.',
                    'delete-success' => 'Webhook logs deleted successfully',
                    'delete-failed'  => 'Webhook logs deletion failed unexpectedly',
                    'unauthorized'   => 'This action is unauthorized',
                ],
            ],
        ],
    ],
];
