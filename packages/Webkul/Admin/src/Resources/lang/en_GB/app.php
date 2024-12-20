<?php

return [
    'users' => [
        'sessions' => [
            'email'                => 'Email Address',
            'forget-password-link' => 'Forgot Password?',
            'password'             => 'Password',
            'submit-btn'           => 'Sign In',
            'title'                => 'Sign In',
        ],

        'forget-password' => [
            'create' => [
                'email'                => 'Registered Email',
                'email-not-exist'      => 'Email Does Not Exist',
                'page-title'           => 'Forgot Password',
                'reset-link-sent'      => 'Reset Password link sent',
                'email-settings-error' => 'Email could not be sent. Please check your email configuration details',
                'sign-in-link'         => 'Back to Sign In?',
                'submit-btn'           => 'Reset',
                'title'                => 'Recover Password',
            ],
        ],

        'reset-password' => [
            'back-link-title'  => 'Back to Sign In?',
            'confirm-password' => 'Confirm Password',
            'email'            => 'Registered Email',
            'password'         => 'Password',
            'submit-btn'       => 'Reset Password',
            'title'            => 'Reset Password',
        ],
    ],

    'notifications' => [
        'description-text' => 'List all Notifications',
        'marked-success'   => 'Notification Marked Successfully',
        'no-record'        => 'No Records Found',
        'read-all'         => 'Mark as Read',
        'title'            => 'Notifications',
        'view-all'         => 'View All',
        'status'           => [
            'all'        => 'All',
            'canceled'   => 'Cancelled',
            'closed'     => 'Closed',
            'completed'  => 'Completed',
            'pending'    => 'Pending',
            'processing' => 'Processing',
        ],
    ],

    'account' => [
        'edit' => [
            'back-btn'          => 'Back',
            'change-password'   => 'Change Password',
            'confirm-password'  => 'Confirm Password',
            'current-password'  => 'Current Password',
            'email'             => 'Email',
            'general'           => 'General',
            'invalid-password'  => 'The current password you entered is incorrect.',
            'name'              => 'Name',
            'password'          => 'Password',
            'profile-image'     => 'Profile Image',
            'save-btn'          => 'Save Account',
            'title'             => 'My Account',
            'ui-locale'         => 'UI Locale',
            'update-success'    => 'Account updated successfully',
            'upload-image-info' => 'Upload a Profile Image (110px X 110px)',
            'user-timezone'     => 'Timezone',
        ],
    ],

    'dashboard' => [
        'index' => [
            'title'            => 'Dashboard',
            'user-info'        => 'Quickly monitor what counts in your PIM',
            'user-name'        => 'Hi! :user_name',
            'catalog-details'  => 'Catalogue',
            'total-families'   => 'Total Families',
            'total-attributes' => 'Total Attributes',
            'total-groups'     => 'Total Groups',
            'total-categories' => 'Total Categories',
            'total-products'   => 'Total Products',
            'settings-details' => 'Catalogue Structure',
            'total-locales'    => 'Total Locales',
            'total-currencies' => 'Total Currencies',
            'total-channels'   => 'Total Channels',
        ],
    ],

    'acl' => [
        'addresses'                => 'Addresses',
        'attribute-families'       => 'Attribute Families',
        'attribute-groups'         => 'Attribute Groups',
        'attributes'               => 'Attributes',
        'cancel'                   => 'Cancel',
        'catalog'                  => 'Catalog',
        'categories'               => 'Categories',
        'channels'                 => 'Channels',
        'configure'                => 'Configure',
        'configuration'            => 'Configuration',
        'copy'                     => 'Copy',
        'create'                   => 'Create',
        'currencies'               => 'Currencies',
        'dashboard'                => 'Dashboard',
        'data-transfer'            => 'Data Transfer',
        'delete'                   => 'Delete',
        'edit'                     => 'Edit',
        'email-templates'          => 'Email Templates',
        'events'                   => 'Events',
        'groups'                   => 'Groups',
        'import'                   => 'Import',
        'imports'                  => 'Imports',
        'invoices'                 => 'Invoices',
        'locales'                  => 'Locales',
        'magic-ai'                 => 'Magic AI',
        'marketing'                => 'Marketing',
        'newsletter-subscriptions' => 'Newsletter Subscriptions',
        'note'                     => 'Note',
        'orders'                   => 'Orders',
        'products'                 => 'Products',
        'promotions'               => 'Promotions',
        'refunds'                  => 'Refunds',
        'reporting'                => 'Reporting',
        'reviews'                  => 'Reviews',
        'roles'                    => 'Roles',
        'sales'                    => 'Sales',
        'search-seo'               => 'Search & SEO',
        'search-synonyms'          => 'Search Synonyms',
        'search-terms'             => 'Search Terms',
        'settings'                 => 'Settings',
        'shipments'                => 'Shipments',
        'sitemaps'                 => 'Sitemaps',
        'subscribers'              => 'Newsletter Subscribers',
        'tax-categories'           => 'Tax Categories',
        'tax-rates'                => 'Tax Rates',
        'taxes'                    => 'Taxes',
        'themes'                   => 'Themes',
        'integration'              => 'Integration',
        'url-rewrites'             => 'URL Rewrites',
        'users'                    => 'Users',
        'category_fields'          => 'Category Fields',
        'view'                     => 'View',
        'execute'                  => 'Job Execute',
        'history'                  => 'History',
        'restore'                  => 'Restore',
        'integrations'             => 'Integrations',
        'api'                      => 'API',
        'tracker'                  => 'Job Tracker',
        'imports'                  => 'Imports',
        'exports'                  => 'Exports',
    ],

    'errors' => [
        'dashboard' => 'Dashboard',
        'go-back'   => 'Go Back',
        'support'   => 'If the problem persists, reach out to us at <a href=":link" class=":class">:email</a> for assistance.',

        '404' => [
            'description' => 'Oops! The page you\'re looking for is on vacation. We couldn\'t find what you were searching for.',
            'title'       => '404 Page Not Found',
        ],

        '401' => [
            'description' => 'Oops! Looks like you\'re not allowed to access this page. You\'re missing the necessary credentials.',
            'title'       => '401 Unauthorized',
            'message'     => 'Authentication failed due to invalid credentials or expired token.',
        ],

        '403' => [
            'description' => 'Oops! This page is off-limits. You don\'t have the required permissions to view this content.',
            'title'       => '403 Forbidden',
        ],

        '413' => [
            'description' => 'Oops! It seems you are trying to upload a file that is too large. If you want to upload the same, please update the PHP configuration accordingly.',
            'title'       => '413 Content Too Large',
        ],

        '419' => [
            'description' => 'Oops! Your session has expired. Please refresh the page and log in again to continue.',
            'title'       => '419 Session Has Expired',
        ],

        '500' => [
            'description' => 'Oops! Something went wrong. We\'re having trouble loading the page you\'re looking for.',
            'title'       => '500 Internal Server Error',
        ],

        '503' => [
            'description' => 'Oops! Looks like we\'re temporarily down for maintenance. Please check back in a bit.',
            'title'       => '503 Service Unavailable',
        ],
    ],

    'export' => [
        'csv'        => 'CSV',
        'download'   => 'Download',
        'export'     => 'Quick Export',
        'no-records' => 'Nothing to export',
        'xls'        => 'XLS',
        'xlsx'       => 'XLSX',
    ],

    'validations' => [
        'slug-being-used' => 'This slug is getting used in either categories or products.',
        'slug-reserved'   => 'This slug is reserved.',
        'invalid-locale'  => 'Invalid locales :locales',
    ],

    'footer' => [
        'copy-right' => 'Powered by <a href="https://unopim.com/" target="_blank">UnoPim</a>, A Community Project by <a href="https://webkul.com/" target="_blank">Webkul</a>',
    ],

    'emails' => [
        'dear'   => 'Dear :admin_name',
        'thanks' => 'If you need any kind of help please contact us at <a href=":link" style=":style">:email</a>.<br/>Thanks!',

        'admin' => [
            'forgot-password' => [
                'description'    => 'You are receiving this email because we received a password reset request for your account.',
                'greeting'       => 'Forgot Password!',
                'reset-password' => 'Reset Password',
                'subject'        => 'Reset Password Email',
            ],
        ],
    ],

    'common' => [
        'yes'     => 'Yes',
        'no'      => 'No',
        'true'    => 'True',
        'false'   => 'False',
        'enable'  => 'Enabled',
        'disable' => 'Disabled',
    ],

    'configuration' => [
        'index' => [
            'delete'                       => 'Delete',
            'no-result-found'              => 'No results found',
            'save-btn'                     => 'Save Configuration',
            'save-message'                 => 'Configuration saved successfully',
            'search'                       => 'Search',
            'title'                        => 'Configuration',

            'general' => [
                'info'  => '',
                'title' => 'General',

                'general' => [
                    'info'  => '',
                    'title' => 'General',
                ],

                'magic-ai' => [
                    'info'  => 'Set Magic AI options.',
                    'title' => 'Magic AI',

                    'settings' => [
                        'api-key'        => 'API Key',
                        'enabled'        => 'Enabled',
                        'llm-api-domain' => 'LLM API Domain',
                        'organization'   => 'Organization ID',
                        'title'          => 'General Settings',
                        'title-info'     => 'Enhance your experience with Magic AI by entering your exclusive API Key and specifying the relevant Organization for seamless integration. Take control of your OpenAI credentials and customize the settings according to your specific needs.',
                    ],
                ],
            ],
        ],

        'integrations' => [
            'index' => [
                'create-btn' => 'Create',
                'title'      => 'Integrations',

                'datagrid' => [
                    'delete'          => 'Delete',
                    'edit'            => 'Edit',
                    'id'              => 'ID',
                    'name'            => 'Name',
                    'user'            => 'User',
                    'client-id'       => 'Client ID',
                    'permission-type' => 'Permission Type',
                ],
            ],

            'create' => [
                'access-control' => 'Access Control',
                'all'            => 'All',
                'back-btn'       => 'Back',
                'custom'         => 'Custom',
                'assign-user'    => 'Assign User',
                'general'        => 'General',
                'name'           => 'Name',
                'permissions'    => 'Permissions',
                'save-btn'       => 'Save',
                'title'          => 'New Integration',
            ],

            'edit' => [
                'access-control' => 'Access Control',
                'all'            => 'All',
                'back-btn'       => 'Back',
                'custom'         => 'Custom',
                'assign-user'    => 'Assign User',
                'general'        => 'General',
                'name'           => 'Name',
                'credentials'    => 'Credentials',
                'client-id'      => 'Client ID',
                'secret-key'     => 'Secret Key',
                'generate-btn'   => 'Generate',
                're-secret-btn'  => 'Re-Generate Secret Key',
                'permissions'    => 'Permissions',
                'save-btn'       => 'Save',
                'title'          => 'Edit Integration',
            ],

            'being-used'                     => 'API Integration is already used in Admin User',
            'create-success'                 => 'API Integration Created Successfully',
            'delete-failed'                  => 'API Integration is deleted failed',
            'delete-success'                 => 'API Integration is deleted successfully',
            'last-delete-error'              => 'Last API Integration can not be deleted',
            'update-success'                 => 'API Integration is updated successfully',
            'generate-key-success'           => 'API key is generated successfully',
            're-generate-secret-key-success' => 'API secret key is regenerated successfully',
            'client-not-found'               => 'Client Not Found',
        ],
    ],

    'components' => [
        'layouts' => [
            'header' => [
                'account-title' => 'Account',
                'app-version'   => 'Version : :version',
                'logout'        => 'Logout',
                'my-account'    => 'My Account',
                'notifications' => 'Notifications',
                'visit-shop'    => 'Visit Shop',
            ],

            'sidebar' => [
                'attribute-families'       => 'Attribute Families',
                'attribute-groups'         => 'Attribute Groups',
                'attributes'               => 'Attributes',
                'history'                  => 'History',
                'edit-section'             => 'Data',
                'general'                  => 'General',
                'catalog'                  => 'Catalog',
                'categories'               => 'Categories',
                'category_fields'          => 'Category Fields',
                'channels'                 => 'Channels',
                'collapse'                 => 'Collapse',
                'configure'                => 'Configuration',
                'currencies'               => 'Currencies',
                'dashboard'                => 'Dashboard',
                'data-transfer'            => 'Data Transfer',
                'groups'                   => 'Groups',
                'tracker'                  => 'Job Tracker',
                'imports'                  => 'Imports',
                'exports'                  => 'Exports',
                'locales'                  => 'Locales',
                'magic-ai'                 => 'Magic AI',
                'mode'                     => 'Dark Mode',
                'products'                 => 'Products',
                'roles'                    => 'Roles',
                'settings'                 => 'Settings',
                'themes'                   => 'Themes',
                'users'                    => 'Users',
                'integrations'             => 'Integrations',
            ],
        ],

        'datagrid' => [
            'index' => [
                'no-records-selected'              => 'No records have been selected.',
                'must-select-a-mass-action-option' => 'You must select a mass action\'s option.',
                'must-select-a-mass-action'        => 'You must select a mass action.',
            ],

            'toolbar' => [
                'length-of' => ':length of',
                'of'        => 'of',
                'per-page'  => 'Per Page',
                'results'   => ':total Results',
                'selected'  => ':total Selected',

                'mass-actions' => [
                    'submit'        => 'Submit',
                    'select-option' => 'Select Option',
                    'select-action' => 'Select Action',
                ],

                'filter' => [
                    'title' => 'Filter',
                ],

                'search_by' => [
                    'code'       => 'Search by code',
                    'code_or_id' => 'Search by code or id',
                ],

                'search' => [
                    'title' => 'Search',
                ],
            ],

            'filters' => [
                'select'   => 'Select',
                'title'    => 'Apply Filters',
                'save'     => 'Save',
                'dropdown' => [
                    'searchable' => [
                        'atleast-two-chars' => 'Type at least 2 characters...',
                        'no-results'        => 'No result found...',
                    ],
                ],

                'custom-filters' => [
                    'clear-all' => 'Clear All',
                    'title'     => 'Custom Filters',
                ],

                'boolean-options' => [
                    'false' => 'False',
                    'true'  => 'True',
                ],

                'date-options' => [
                    'last-month'        => 'Last Month',
                    'last-six-months'   => 'Last 6 Months',
                    'last-three-months' => 'Last 3 Months',
                    'this-month'        => 'This Month',
                    'this-week'         => 'This Week',
                    'this-year'         => 'This Year',
                    'today'             => 'Today',
                    'yesterday'         => 'Yesterday',
                ],
            ],

            'table' => [
                'actions'              => 'Actions',
                'no-records-available' => 'No Records Available.',
            ],
        ],

        'modal' => [
            'confirm' => [
                'agree-btn'    => 'Agree',
                'disagree-btn' => 'Disagree',
                'message'      => 'Are you sure you want to perform this action?',
                'title'        => 'Are you sure?',
            ],

            'delete' => [
                'agree-btn'    => 'Delete',
                'disagree-btn' => 'Cancel',
                'message'      => 'Are you sure you want to delete?',
                'title'        => 'Confirm Deletion',
            ],

            'history' => [
                'title'           => 'History Preview',
                'subtitle'        => 'Quickly review your updates and changes.',
                'close-btn'       => 'Close',
                'version-label'   => 'Version',
                'date-time-label' => 'Date/Time',
                'user-label'      => 'User',
                'name-label'      => 'Key',
                'old-value-label' => 'Old Value',
                'new-value-label' => 'New Value',
                'no-history'      => 'No history Found',
            ],
        ],

        'products' => [
            'search' => [
                'add-btn'       => 'Add Selected Product',
                'empty-info'    => 'No products available for search term.',
                'empty-title'   => 'No products found',
                'product-image' => 'Product Image',
                'qty'           => ':qty Available',
                'sku'           => 'SKU - :sku',
                'title'         => 'Select Products',
            ],
        ],

        'media' => [
            'images' => [
                'add-image-btn'     => 'Add Image',
                'ai-add-image-btn'  => 'Magic AI',
                'ai-btn-info'       => 'Generate Image',
                'allowed-types'     => 'png, jpeg, jpg',
                'not-allowed-error' => 'Only image files (.jpeg, .jpg, .png, ..) are allowed.',

                'ai-generation' => [
                    '1024x1024'        => '1024x1024',
                    '1024x1792'        => '1024x1792',
                    '1792x1024'        => '1792x1024',
                    'apply'            => 'Apply',
                    'dall-e-2'         => 'Dall.E 2',
                    'dall-e-3'         => 'Dall.E 3',
                    'generate'         => 'Generate',
                    'generating'       => 'Generating...',
                    'hd'               => 'HD',
                    'model'            => 'Model',
                    'number-of-images' => 'Number of Images',
                    'prompt'           => 'Prompt',
                    'quality'          => 'Quality',
                    'regenerate'       => 'Regenerate',
                    'regenerating'     => 'Regenerating...',
                    'size'             => 'Size',
                    'standard'         => 'Standard',
                    'title'            => 'AI Image Generation',
                ],

                'placeholders' => [
                    'front'     => 'Front',
                    'next'      => 'Next',
                    'size'      => 'Size',
                    'use-cases' => 'Use Cases',
                    'zoom'      => 'Zoom',
                ],
            ],

            'videos' => [
                'add-video-btn'     => 'Add Video',
                'allowed-types'     => 'mp4, webm, mkv',
                'not-allowed-error' => 'Only video files (.mp4, .mov, .ogg ..) are allowed.',
            ],

            'files' => [
                'add-file-btn'      => 'Add File',
                'allowed-types'     => 'pdf',
                'not-allowed-error' => 'Only pdf files are allowed',
            ],
        ],

        'tinymce' => [
            'ai-btn-tile' => 'Magic AI',

            'ai-generation' => [
                'apply'                  => 'Apply',
                'generate'               => 'Generate',
                'generated-content'      => 'Generated Content',
                'generated-content-info' => 'AI content can be misleading. Please review the generated content before applying it.',
                'generating'             => 'Generating...',
                'prompt'                 => 'Prompt',
                'title'                  => 'AI Assistance',
                'model'                  => 'Model',
                'gpt-3-5-turbo'          => 'OpenAI gpt-3.5-turbo',
                'llama2'                 => 'Llama 2',
                'mistral'                => 'Mistral',
                'dolphin-phi'            => 'Dolphin Phi',
                'phi'                    => 'Phi-2',
                'starling-lm'            => 'Starling',
                'llama2-uncensored'      => 'Llama 2 Uncensored',
                'llama2:13b'             => 'Llama 2 13B',
                'llama2:70b'             => 'Llama 2 70B',
                'orca-mini'              => 'Orca Mini',
                'vicuna'                 => 'Vicuna',
                'llava'                  => 'LLaVA',
            ],
        ],
    ],
];
