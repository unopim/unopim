<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'Completeness',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'Completeness updated successfully',
                    'title'               => 'Completeness',
                    'configure'           => 'Configure Completeness',
                    'channel-required'    => 'Required in Channels',
                    'save-btn'            => 'Save',
                    'back-btn'            => 'Back',
                    'mass-update-success' => 'Completeness updated successfully',
                    'datagrid'            => [
                        'code'             => 'Code',
                        'name'             => 'Name',
                        'channel-required' => 'Required in Channels',
                        'actions'          => [
                            'change-requirement' => 'Change Completeness Requirement',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'N/A',
                    'completeness'                 => 'Complete',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'Completeness',
                    'subtitle' => 'Average completeness',
                ],
                'required-attributes' => 'missing required attributes',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'Completeness Calculation Completed',
        'completeness-calculated'        => 'Completeness calculated for :count products.',
        'completeness-calculated-family' => 'Completeness calculated for :count products in family ":family".',
        'email-subject'                  => 'Completeness Calculation Completed',
        'email-greeting'                 => 'Hello,',
        'email-body'                     => 'The completeness calculation has been completed for :count products.',
        'email-body-family'              => 'The completeness calculation has been completed for :count products in attribute family ":family".',
        'email-footer'                   => 'You can view the completeness details on your dashboard.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'Calculated products',
                'suggestion'          => [
                    'low'     => 'Low completeness, add details to improve.',
                    'medium'  => 'Keep going, continue adding information.',
                    'high'    => 'Almost complete, just a few details left.',
                    'perfect' => 'Product information is fully complete.',
                ],
            ],
        ],
    ],
];
