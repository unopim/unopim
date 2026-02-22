<?php

return [
    'acl' => [
        'pricing'         => 'Pricing',
        'costs'           => 'Product Costs',
        'channel-costs'   => 'Channel Costs',
        'margins'         => 'Margin Protection',
        'recommendations' => 'Price Recommendations',
        'strategies'      => 'Pricing Strategies',
        'view'            => 'View',
        'create'          => 'Create',
        'edit'            => 'Edit',
        'delete'          => 'Delete',
        'approve'         => 'Approve',
        'reject'          => 'Reject',
        'apply'           => 'Apply',
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'pricing'         => 'Pricing',
                'costs'           => 'Product Costs',
                'channel-costs'   => 'Channel Costs',
                'recommendations' => 'Recommendations',
                'margins'         => 'Margin Protection',
                'strategies'      => 'Strategies',
            ],
        ],
    ],

    'costs' => [
        'index' => [
            'title'      => 'Product Costs',
            'create-btn' => 'Add Cost',
        ],

        'create' => [
            'title' => 'Add Product Cost',
        ],

        'edit' => [
            'title' => 'Edit Product Cost',
        ],

        'datagrid' => [
            'id'             => 'ID',
            'product'        => 'Product',
            'cost-type'      => 'Cost Type',
            'amount'         => 'Amount',
            'currency'       => 'Currency',
            'effective-from' => 'Effective From',
            'effective-to'   => 'Effective To',
            'created-by'     => 'Created By',
        ],

        'fields' => [
            'product'        => 'Product',
            'cost-type'      => 'Cost Type',
            'amount'         => 'Amount',
            'currency-code'  => 'Currency',
            'effective-from' => 'Effective From',
            'effective-to'   => 'Effective To',
            'notes'          => 'Notes',
        ],

        'cost-types' => [
            'cogs'        => 'Cost of Goods Sold',
            'operational' => 'Operational',
            'marketing'   => 'Marketing',
            'platform'    => 'Platform',
            'shipping'    => 'Shipping',
            'overhead'    => 'Overhead',
        ],

        'create-success' => 'Product cost created successfully.',
        'update-success' => 'Product cost updated successfully.',
        'delete-success' => 'Product cost deleted successfully.',
        'delete-failed'  => 'Product cost cannot be deleted.',
    ],

    'channel-costs' => [
        'index' => [
            'title'      => 'Channel Costs',
            'create-btn' => 'Add Channel Cost',
        ],

        'datagrid' => [
            'id'                           => 'ID',
            'channel'                      => 'Channel',
            'commission-percentage'         => 'Commission %',
            'fixed-fee-per-order'          => 'Fixed Fee / Order',
            'payment-processing-percentage' => 'Payment Processing %',
            'payment-fixed-fee'            => 'Payment Fixed Fee',
            'effective-from'               => 'Effective From',
            'effective-to'                 => 'Effective To',
        ],

        'fields' => [
            'channel'                       => 'Channel',
            'commission-percentage'         => 'Commission Percentage',
            'fixed-fee-per-order'          => 'Fixed Fee Per Order',
            'payment-processing-percentage' => 'Payment Processing Percentage',
            'payment-fixed-fee'            => 'Payment Fixed Fee',
            'shipping-cost-per-zone'       => 'Shipping Cost Per Zone',
            'currency-code'                => 'Currency',
            'effective-from'               => 'Effective From',
            'effective-to'                 => 'Effective To',
        ],

        'create-success' => 'Channel cost created successfully.',
        'update-success' => 'Channel cost updated successfully.',
    ],

    'break-even' => [
        'title'              => 'Break-Even Analysis',
        'product'            => 'Product',
        'channel'            => 'Channel',
        'total-cost'         => 'Total Cost',
        'break-even-price'   => 'Break-Even Price',
        'current-price'      => 'Current Price',
        'margin'             => 'Margin',
        'margin-percentage'  => 'Margin %',
        'cost-breakdown'     => 'Cost Breakdown',
        'no-costs'           => 'No cost data found for this product.',
    ],

    'recommendations' => [
        'title'               => 'Price Recommendations',
        'product'             => 'Product',
        'channel'             => 'Channel',
        'current-price'       => 'Current Price',
        'recommended-minimum' => 'Recommended Minimum',
        'recommended-target'  => 'Recommended Target',
        'recommended-premium' => 'Recommended Premium',
        'apply-btn'           => 'Apply Price',
        'select-tier'         => 'Select Pricing Tier',

        'tiers' => [
            'minimum' => 'Minimum Margin',
            'target'  => 'Target Margin',
            'premium' => 'Premium Margin',
        ],

        'apply-success' => 'Recommended price applied successfully.',
        'apply-failed'  => 'Failed to apply recommended price.',
    ],

    'margins' => [
        'index' => [
            'title' => 'Margin Protection Events',
        ],

        'show' => [
            'title' => 'Margin Event Details',
        ],

        'datagrid' => [
            'id'                       => 'ID',
            'product'                  => 'Product',
            'channel'                  => 'Channel',
            'event-type'               => 'Event Type',
            'proposed-price'           => 'Proposed Price',
            'break-even-price'         => 'Break-Even Price',
            'margin-percentage'        => 'Margin %',
            'minimum-margin-percentage' => 'Min Margin %',
            'approved-by'              => 'Approved By',
            'created-at'               => 'Created At',
        ],

        'fields' => [
            'product'                   => 'Product',
            'channel'                   => 'Channel',
            'event-type'                => 'Event Type',
            'proposed-price'            => 'Proposed Price',
            'break-even-price'          => 'Break-Even Price',
            'minimum-margin-price'      => 'Minimum Margin Price',
            'target-margin-price'       => 'Target Margin Price',
            'margin-percentage'         => 'Margin Percentage',
            'minimum-margin-percentage' => 'Minimum Margin Percentage',
            'reason'                    => 'Reason',
            'approved-by'               => 'Approved By',
            'approved-at'               => 'Approved At',
            'expires-at'                => 'Expires At',
        ],

        'event-types' => [
            'blocked'  => 'Blocked',
            'warning'  => 'Warning',
            'approved' => 'Approved',
            'expired'  => 'Expired',
        ],

        'approve-success' => 'Margin event approved successfully.',
        'approve-failed'  => 'Failed to approve margin event.',
        'reject-success'  => 'Margin event rejected successfully.',
        'reject-failed'   => 'Failed to reject margin event.',
        'already-resolved' => 'This margin event has already been resolved.',
    ],

    'strategies' => [
        'index' => [
            'title'      => 'Pricing Strategies',
            'create-btn' => 'Create Strategy',
        ],

        'create' => [
            'title' => 'Create Pricing Strategy',
        ],

        'edit' => [
            'title' => 'Edit Pricing Strategy',
        ],

        'datagrid' => [
            'id'                        => 'ID',
            'scope-type'                => 'Scope Type',
            'scope-id'                  => 'Scope ID',
            'minimum-margin-percentage' => 'Min Margin %',
            'target-margin-percentage'  => 'Target Margin %',
            'premium-margin-percentage' => 'Premium Margin %',
            'psychological-pricing'     => 'Psychological Pricing',
            'round-to'                  => 'Round To',
            'is-active'                 => 'Active',
            'priority'                  => 'Priority',
        ],

        'fields' => [
            'scope-type'                => 'Scope Type',
            'scope-id'                  => 'Scope ID',
            'minimum-margin-percentage' => 'Minimum Margin Percentage',
            'target-margin-percentage'  => 'Target Margin Percentage',
            'premium-margin-percentage' => 'Premium Margin Percentage',
            'psychological-pricing'     => 'Psychological Pricing',
            'round-to'                  => 'Round To',
            'is-active'                 => 'Active',
            'priority'                  => 'Priority',
        ],

        'scope-types' => [
            'global'   => 'Global',
            'category' => 'Category',
            'channel'  => 'Channel',
            'product'  => 'Product',
        ],

        'round-to-options' => [
            '0.99' => 'x.99',
            '0.95' => 'x.95',
            '0.00' => 'x.00',
            'none' => 'No Rounding',
        ],

        'create-success' => 'Pricing strategy created successfully.',
        'update-success' => 'Pricing strategy updated successfully.',
        'delete-success' => 'Pricing strategy deleted successfully.',
        'delete-failed'  => 'Pricing strategy cannot be deleted.',
    ],

    'validation' => [
        'amount-required'          => 'The amount field is required.',
        'amount-numeric'           => 'The amount must be a number.',
        'amount-min'               => 'The amount must be at least :min.',
        'cost-type-required'       => 'The cost type field is required.',
        'cost-type-invalid'        => 'The selected cost type is invalid.',
        'product-required'         => 'The product field is required.',
        'channel-required'         => 'The channel field is required.',
        'effective-from-required'  => 'The effective from date is required.',
        'effective-from-date'      => 'The effective from must be a valid date.',
        'effective-to-after'       => 'The effective to date must be after the effective from date.',
        'scope-type-required'      => 'The scope type field is required.',
        'scope-type-invalid'       => 'The selected scope type is invalid.',
        'margin-min'               => 'The margin percentage must be at least :min.',
        'margin-max'               => 'The margin percentage must not exceed :max.',
        'tier-required'            => 'A pricing tier must be selected.',
        'tier-invalid'             => 'The selected pricing tier is invalid.',
    ],

    'errors' => [
        'PRC-001' => 'Product not found.',
        'PRC-002' => 'Channel not found.',
        'PRC-003' => 'No cost data available for this product.',
        'PRC-004' => 'No active pricing strategy found.',
        'PRC-005' => 'Margin violation: proposed price is below break-even.',
        'PRC-006' => 'Duplicate cost entry for the same product, type, and effective date.',
        'PRC-007' => 'Cannot delete cost with active margin protection events.',
        'PRC-008' => 'Strategy scope conflict: a strategy already exists for this scope.',
        'PRC-009' => 'Invalid currency code.',
        'PRC-010' => 'Margin event already resolved.',
    ],

    'general' => [
        'save'    => 'Save',
        'cancel'  => 'Cancel',
        'confirm' => 'Are you sure?',
        'yes'     => 'Yes',
        'no'      => 'No',
        'back'    => 'Back',
        'actions' => 'Actions',
        'approve' => 'Approve',
        'reject'  => 'Reject',
        'apply'   => 'Apply',
    ],
];
