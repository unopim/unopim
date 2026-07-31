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
        'delete-failed' => 'Please enable webhook from settings',
        'success'       => 'The product data sent to webhook successfully',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'Webhook',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],
        'logs' => [
            'index'       => 'Logs',
            'view'        => 'View',
            'delete'      => 'Delete',
            'mass-delete' => 'Mass Delete',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Product Created',
            'updated' => 'Product Updated',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Webhooks',
            'create-btn'   => 'Create Webhook',
            'logs-btn'     => 'Logs',
            'back-btn'     => 'Back to Webhooks',
            'default-name' => 'Default',
            'datagrid'     => [
                'id'         => 'Id',
                'name'       => 'Name',
                'url'        => 'URL',
                'events'     => 'Events',
                'status'     => 'Status',
                'active'     => 'Active',
                'inactive'   => 'Inactive',
                'created_at' => 'Created At',
                'edit'       => 'Edit',
                'delete'     => 'Delete',
            ],
        ],
        'create' => [
            'title'    => 'Create Webhook',
            'save-btn' => 'Save',
        ],
        'edit' => [
            'title'    => 'Edit Webhook',
            'save-btn' => 'Save',
        ],
        'form' => [
            'general'       => 'General',
            'name'          => 'Name',
            'url'           => 'URL',
            'events'        => 'Events',
            'select-events' => 'Select events',
            'secret'        => 'Signing Secret',
            'secret-set'    => 'A secret is already set',
            'secret-hint'   => 'Used to sign each payload with an HMAC SHA-256 signature. Leave blank to keep the current secret.',
            'settings'      => 'Settings',
            'active'        => 'Active',
            'test'          => 'Test Connection',
            'test-hint'     => 'Send a test request to the URL above.',
            'test-btn'      => 'Send Test',
            'test-no-url'   => 'Please enter a URL first.',
            'test-failed'   => 'The test request failed.',
            'headers'       => 'Custom Headers',
            'add-header'    => 'Add Header',
            'no-headers'    => 'No custom headers added.',
            'header-key'    => 'Header',
            'header-value'  => 'Value',
        ],
        'create-success' => 'Webhook created successfully',
        'update-success' => 'Webhook updated successfully',
        'delete-success' => 'Webhook deleted successfully',
        'delete-failed'  => 'Webhook deletion failed',
        'validation'     => [
            'unsafe-url' => 'The URL points at a private, loopback or internal address and is not allowed.',
            'scheme'     => 'The URL must start with http:// or https://.',
        ],
        'test' => [
            'payload-message'   => 'Unopim webhook test request',
            'connection-failed' => 'The URL could not be reached. Please check the URL.',
            'unreachable'       => 'The URL is not reachable (HTTP :code).',
            'reachable'         => 'The URL is reachable.',
        ],
        'prune' => [
            'disabled' => 'Webhook log retention is disabled; nothing was pruned.',
            'done'     => 'Pruned :count webhook log(s) older than :days day(s).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'Id',
                        'webhook'          => 'Webhook',
                        'sku'              => 'SKU',
                        'event'            => 'Event',
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
                    'load-failed'    => 'Failed to load log details.',
                    'delete-success' => 'Webhook logs deleted successfully',
                    'delete-failed'  => 'Webhook logs deletion failed unexpectedly',
                    'unauthorized'   => 'This action is unauthorized',
                ],
            ],
        ],
    ],
];
