<?php

return [
    'acl' => [
        'channel-connectors' => 'Channel Connectors',
        'connectors'         => 'Connectors',
        'mappings'           => 'Field Mappings',
        'sync'               => 'Sync Jobs',
        'conflicts'          => 'Sync Conflicts',
        'webhooks'           => 'Webhooks',
        'view'               => 'View',
        'create'             => 'Create',
        'edit'               => 'Edit',
        'delete'             => 'Delete',
        'manage'             => 'Manage',
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'channel-connectors' => 'Channel Connectors',
                'connectors'         => 'Connectors',
                'sync-monitor'       => 'Sync Monitor',
                'conflicts'          => 'Conflicts',
            ],
        ],
    ],

    'connectors' => [
        'index' => [
            'title'      => 'Channel Connectors',
            'create-btn' => 'Create Connector',
        ],

        'create' => [
            'title' => 'Create Channel Connector',
        ],

        'edit' => [
            'title' => 'Edit Channel Connector',
        ],

        'datagrid' => [
            'code'           => 'Code',
            'name'           => 'Name',
            'channel-type'   => 'Channel Type',
            'status'         => 'Status',
            'last-synced-at' => 'Last Synced',
        ],

        'create-success'    => 'Connector created successfully.',
        'update-success'    => 'Connector updated successfully.',
        'delete-success'    => 'Connector deleted successfully.',
        'delete-failed'     => 'Connector cannot be deleted.',
        'test-success'      => 'Connection verified successfully.',
        'test-failed'       => 'Connection test failed: :reason',
        'duplicate-running' => 'A sync job is already running for this connector.',

        'status' => [
            'connected'    => 'Connected',
            'disconnected' => 'Disconnected',
            'error'        => 'Error',
        ],

        'channel-types' => [
            'shopify'      => 'Shopify',
            'salla'        => 'Salla',
            'easy_orders'  => 'Easy Orders',
        ],

        'fields' => [
            'code'              => 'Code',
            'name'              => 'Name',
            'channel-type'      => 'Channel Type',
            'credentials'       => 'Credentials',
            'shop-url'          => 'Shop URL',
            'access-token'      => 'Access Token',
            'api-key'           => 'API Key',
            'status'            => 'Status',
            'conflict-strategy' => 'Default Conflict Strategy',
            'inbound-strategy'  => 'Inbound Webhook Strategy',
            'access-token-help' => 'Leave blank to keep existing credentials.',
        ],

        'conflict-strategies' => [
            'always_ask'          => 'Always Ask',
            'pim_always_wins'     => 'PIM Always Wins',
            'channel_always_wins' => 'Channel Always Wins',
        ],

        'conflict-strategy-help' => 'Determines how conflicts are handled when both PIM and the channel have modified the same product since the last sync.',

        'inbound-strategies' => [
            'auto_update'      => 'Auto-Update PIM',
            'flag_for_review'  => 'Flag for Review',
            'ignore'           => 'Ignore',
        ],
    ],

    'mappings' => [
        'index' => [
            'title' => 'Field Mappings',
        ],

        'save-success'     => 'Mappings saved successfully.',
        'save-failed'      => 'Failed to save mappings.',
        'translatable'     => 'Translatable',
        'auto-suggest'     => 'Auto-Suggest',
        'preview'          => 'Preview',

        'direction' => [
            'export' => 'Export',
            'import' => 'Import',
            'both'   => 'Both',
        ],

        'fields' => [
            'unopim-attribute' => 'UnoPim Attribute',
            'channel-field'    => 'Channel Field',
            'direction'        => 'Direction',
            'transformation'   => 'Transformation',
            'locale-mapping'   => 'Locale Mapping',
        ],

        'actions' => [
            'add'               => 'Add Mapping',
            'apply-suggestions' => 'Apply Suggestions',
        ],

        'locale-mapping' => [
            'title'          => 'Locale Mapping',
            'unopim-locale'  => 'UnoPim Locale',
            'channel-locale' => 'Channel Locale',
            'unmapped'       => 'Unmapped (will be skipped)',
            'rtl-warning'    => 'RTL content may be modified for this channel.',
        ],
    ],

    'sync' => [
        'index' => [
            'title' => 'Sync Jobs',
            'empty' => 'No sync jobs yet.',
        ],

        'show' => [
            'title'            => 'Sync Job Details',
            'percent-complete' => 'complete',
        ],

        'trigger-success' => 'Sync job queued successfully.',
        'retry-success'   => 'Retry job queued successfully.',
        'trigger-failed'  => 'Failed to start sync job.',

        'status' => [
            'pending'   => 'Pending',
            'running'   => 'Running',
            'completed' => 'Completed',
            'failed'    => 'Failed',
            'retrying'  => 'Retrying',
        ],

        'types' => [
            'full'        => 'Full Sync',
            'incremental' => 'Incremental Sync',
            'single'      => 'Single Product',
        ],

        'fields' => [
            'connector'        => 'Connector',
            'sync-type'        => 'Sync Type',
            'status'           => 'Status',
            'total-products'   => 'Total Products',
            'synced-products'  => 'Synced',
            'failed-products'  => 'Failed',
            'started-at'       => 'Started At',
            'completed-at'     => 'Completed At',
            'duration'         => 'Duration',
            'progress'         => 'Progress',
        ],

        'actions' => [
            'trigger-sync'    => 'Start Sync',
            'trigger-full'    => 'Full Sync',
            'trigger-incr'    => 'Incremental Sync',
            'retry-failed'    => 'Retry Failed',
            'confirm-full'    => 'Full sync will process all products. This may take a while. Continue?',
        ],

        'errors' => [
            'title'      => 'Error Details',
            'product'    => 'Product',
            'error-code' => 'Error Code',
            'message'    => 'Message',
        ],
    ],

    'conflicts' => [
        'index' => [
            'title' => 'Sync Conflicts',
        ],

        'show' => [
            'title'            => 'Conflict Details',
            'field-comparison' => 'Field Comparison',
            'common'           => 'Common',
            'field'            => 'Field',
            'winner'           => 'Winner',
            'pim'              => 'PIM',
            'channel'          => 'Channel',
            'locale'           => 'Locale',
        ],

        'resolve-success'    => 'Conflict resolved successfully.',
        'resolve-failed'     => 'Failed to resolve conflict.',
        'already-resolved'   => 'This conflict has already been resolved (:status).',
        'resolution-details' => 'Resolution Details',

        'resolution' => [
            'pending'      => 'Pending',
            'unresolved'   => 'Unresolved',
            'pim_wins'     => 'PIM Wins',
            'channel_wins' => 'Channel Wins',
            'merged'       => 'Manual Merge',
            'dismissed'    => 'Dismissed',
        ],

        'conflict-types' => [
            'both_modified'      => 'Both Modified',
            'field_mismatch'     => 'Field Mismatch',
            'deleted_in_pim'     => 'Deleted in PIM',
            'deleted_in_channel' => 'Deleted in Channel',
            'new_in_channel'     => 'New in Channel',
        ],

        'fields' => [
            'product'             => 'Product',
            'connector'           => 'Connector',
            'conflict-type'       => 'Conflict Type',
            'resolution-status'   => 'Resolution Status',
            'pim-value'           => 'PIM Value',
            'channel-value'       => 'Channel Value',
            'pim-modified-at'     => 'PIM Modified At',
            'channel-modified-at' => 'Channel Modified At',
            'resolved-by'         => 'Resolved By',
            'resolved-at'         => 'Resolved At',
        ],

        'actions' => [
            'resolve'          => 'Resolve',
            'pim-wins-all'     => 'PIM Wins (All Fields)',
            'channel-wins-all' => 'Channel Wins (All Fields)',
            'dismiss'          => 'Dismiss',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title' => 'Webhooks',
        ],

        'fields' => [
            'webhook-url'      => 'Webhook URL',
            'events'           => 'Events',
            'status'           => 'Status',
            'last-received'    => 'Last Received',
            'inbound-strategy' => 'Inbound Strategy',
        ],

        'events' => [
            'product-created' => 'Product Created',
            'product-updated' => 'Product Updated',
            'product-deleted' => 'Product Deleted',
        ],

        'copy-url'              => 'Copy URL',
        'url-copied'            => 'Webhook URL copied to clipboard.',
        'register-success'      => 'Webhooks registered successfully.',
        'unregister-success'    => 'Webhooks unregistered successfully.',
        'manage-success'        => 'Webhook settings saved successfully.',
        'no-token'              => 'Webhook token will be generated when you save settings.',
        'save-settings'         => 'Save Webhook Settings',
        'event-subscriptions'   => 'Event Subscriptions',
        'webhook-url-info'      => 'Use this URL to receive webhook notifications from your channel.',
        'inbound-strategy-info' => 'Controls how inbound webhook data is processed.',
    ],

    'errors' => [
        'CHN-001' => 'Invalid channel type.',
        'CHN-002' => 'Invalid credentials format.',
        'CHN-003' => 'Connection test failed.',
        'CHN-004' => 'OAuth2 authorization failed.',
        'CHN-005' => 'OAuth2 token refresh failed.',
        'CHN-010' => 'Missing required channel field: :field',
        'CHN-011' => 'Field type mismatch for :field.',
        'CHN-012' => 'Locale not supported by channel: :locale',
        'CHN-013' => 'Currency not supported by channel: :currency',
        'CHN-020' => 'Channel API rate limit exceeded.',
        'CHN-021' => 'Channel API temporarily unavailable.',
        'CHN-022' => 'Channel API returned error: :message',
        'CHN-023' => 'Channel API timeout.',
        'CHN-030' => 'Product not found in channel.',
        'CHN-031' => 'Product create failed in channel.',
        'CHN-032' => 'Product update failed in channel.',
        'CHN-033' => 'Product delete failed in channel.',
        'CHN-040' => 'Sync conflict detected.',
        'CHN-041' => 'Conflict resolution failed.',
        'CHN-050' => 'Webhook signature invalid.',
        'CHN-051' => 'Webhook payload parse error.',
        'CHN-052' => 'Webhook event type not supported.',
        'CHN-060' => 'Field mapping validation failed.',
        'CHN-061' => 'Broken field mapping (attribute deleted).',
        'CHN-070' => 'Tax calculation error.',
        'CHN-071' => 'Commission tracking error.',
        'CHN-080' => 'Tenant isolation violation.',
        'CHN-090' => 'Sync job already running for this connector.',
        'CHN-091' => 'Retry job source not found.',
    ],

    'dashboard' => [
        'title'              => 'Sync Monitor',
        'back-to-connectors' => 'Back to Connectors',
        'retry-only-failed'  => 'Only failed jobs can be retried.',

        'datagrid' => [
            'id' => 'ID',
        ],

        'show' => [
            'title'         => 'Sync Job Details',
            'retry-history' => 'Retry History',
        ],

        'progress' => [
            'products-processed' => 'products processed',
            'synced'             => 'synced',
            'failed'             => 'failed',
            'eta'                => 'ETA',
            'polling'            => 'Live',
        ],
    ],

    'general' => [
        'save'            => 'Save',
        'cancel'          => 'Cancel',
        'confirm'         => 'Are you sure?',
        'yes'             => 'Yes',
        'no'              => 'No',
        'back'            => 'Back',
        'actions'         => 'Actions',
        'test-connection' => 'Test Connection',
        'view'            => 'View',
        'store'           => 'Store',
        'products'        => 'Products',
    ],
];
