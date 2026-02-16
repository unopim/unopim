<?php

return [
    'exporters' => [
        'shopify' => [
            'product'    => 'Shopify Product',
            'category'   => 'Shopify Category',
            'metafields' => 'Shopify Metafields Definition',
        ],
    ],
    'importers' => [
        'shopify' => [
            'product'   => 'Shopify Product',
            'category'  => 'Shopify Category',
            'attribute' => 'Shopify Attribute',
            'family'    => 'Shopify Family Variant Attribute Assignment',
            'metafield' => 'Shopify Metafield Definitions',
        ],
    ],
    'components' => [
        'layouts' => [
            'sidebar' => [
                'settings'        => 'Settings',
                'shopify'               => 'Shopify',
                'credentials'           => 'Credentials',
                'export-mappings'       => 'Export Mappings',
                'import-mappings'       => 'Import Mappings',
                'meta-fields'           => 'Metafield Definitions',
                'settings'              => 'Settings',
                'metafield-definitions' => 'Metafield Definitions',
            ],
        ],
    ],

    'shopify' => [
        'acl' => [
            'credential' => [
                'create' => 'Create',
                'edit'   => 'Edit',
                'delete' => 'Delete',
            ],
            'metafield' => [
                'create'      => 'Create Metafield',
                'edit'        => 'Edit Metafield',
                'delete'      => 'Delete Metafield',
                'mass_delete' => 'Mass Delete Metafield',
            ],
        ],

        'version' => 'Version: 1.0.0',

        'credential' => [
            'export' => [
                'locales' => 'Locale Mapping',
            ],
            'shopify' => [
                'locale' => 'Shopify Locale',
            ],
            'unopim' => [
                'locale' => 'Unopim Locale',
            ],
            'delete-success' => 'Credential Deleted Success',
            'created'        => 'Credential Created Success',
            'update-success' => 'Credential Updated Success',
            'invalid'        => 'Invalid Credential',
            'invalidurl'     => 'Invalid URL',
            'already_taken'  => 'The shop url has already been taken.',
            'index'          => [
                'title'                 => 'Shopify Credentials',
                'create'                => 'Create Credential',
                'url'                   => 'Shopify URL',
                'shopifyurlplaceholder' => 'http://demo.myshopify.com',
                'accesstoken'           => 'Admin API access token',
                'apiVersion'            => 'API Version',
                'save'                  => 'Save',
                'back-btn'              => 'Back',
                'channel'               => 'Publishing (Sales channels)',
                'locations'             => 'Location List',
            ],
            'edit' => [
                'title'    => 'Edit Credential',
                'delete'   => 'Delete Credential',
                'back-btn' => 'Back',
                'update'   => 'Update',
                'save'     => 'Save',
            ],
            'datagrid' => [
                'shopUrl'    => 'Shopify URL',
                'apiVersion' => 'API Version',
                'enabled'    => 'Enable',

            ],
        ],
        'export' => [
            'mapping' => [
                'title'         => 'Export Mappings',
                'back-btn'      => 'Back',
                'save'          => 'Save',
                'created'       => 'Export Mapping saved successfully',
                'image'         => 'Attribute to used as image',
                'gallery'       => 'Attribute to used as gallery',
                'metafields'    => 'Attributes to be used as Metafields',
                'filed-shopify' => 'Field in Shopify',
                'attribute'     => 'UnoPim Attribute',
                'fixed-value'   => 'Fixed Value',
                'images'        => [
                    'title' => 'Shopify Media Mapping',
                    'label' => [
                        'type'      => 'Media Type',
                        'attribute' => 'Media Attributes',
                    ],
                ],

                'unit'          => [
                    'title'     => 'Shopify unit Mapping',
                    'weight'    => 'Unit Weight',
                    'volume'    => 'Unit Volume',
                    'dimension' => 'Unit Dimension',
                ],
            ],

            'settings' => [
                'created' => 'Export Settings saved successfully',
            ],

            'setting' => [
                'title'                        => 'Setting',
                'tags'                         => 'Tags Export Setting',
                'enable_metric_tags_attribute' => 'Do you want to pull through the Metric UNIT name as well in tags ?',
                'enable_named_tags_attribute'  => 'Do you want to pull tags as Named Tags',
                'tagSeprator'                  => 'Use Attribute Name Separator in Tags',
                'enable_tags_attribute'        => 'Do you want to pull through the attribute name as well in tags ?',
                'metafields'                   => 'Meta Fields Export Setting',
                'metaFieldsKey'                => 'Use Key for Meta Field as Attribute Code / Label',
                'metaFieldsNameSpace'          => 'Use Namespace for Meta Field as Attribute Group Code / global',
                'credentials'                  => 'Credentials Export',
                'other-settings'               => 'Other Settings',
                'roundof-attribute-value'      => 'Remove Extra fractional Zeros of Metric Attribute Value (e.g. 201.2000 as 201.2)',
                'option_name_label'            => 'Value for Option Name as Attribute Label (By Default Attribute Code)',
            ],

            'errors' => [
                'invalid-credential' => 'Invalid Credential.The credential is either disabled or incorrect',
                'invalid-locale'     => 'Invalid Locale. Please mapp the locale in credential edit section',
            ],
        ],

        'import' => [
            'mapping' => [
                'title'                => 'Import Mappings',
                'back-btn'             => 'Back',
                'save'                 => 'Save',
                'created'              => 'Import Mapping saved successfully',
                'image'                => 'Attribute to used as image',
                'gallery'              => 'Attribute to used as gallery',
                'filed-shopify'        => 'Field in Shopify',
                'attribute'            => 'UnoPim Attribute',
                'variantimage'         => 'Attribute to used as variant image',
                'other'                => 'Shopify Other Mapping',
                'family'               => 'Family mapping (for products)',
                'families'             => 'Choose Family',
                'metafieldDefinitions' => 'Shopify Metafield Definition Mapping',
                'images'               => [
                    'title' => 'Shopify Media Mapping',
                    'label' => [
                        'type'      => 'Media Type',
                        'attribute' => 'Media Attributes',
                    ],
                ],
            ],
            'setting' => [
                'credentialmapping' => 'Credential mapping',
            ],
            'job' => [
                'product' => [
                    'family-not-exist'      => 'Family not exist for the title:- :title 1st you need to import family',
                    'variant-sku-not-exist' => 'Variant SKU not found in product:- :id',
                    'duplicate-sku'         => ':sku :- Duplicate SKU Found in product',
                    'required-field'        => ':attribute :- Field Is required for Sku:- :sku',
                    'family-not-mapping'    => 'family not mapping for the title:- :title',
                    'attribute-not-exist'   => ':attributes Attributes not exist for product',
                    'not-found-sku'         => 'SKU not found in product:- :id',
                    'option-not-found'      => ':attribute - :option Option is not found in the unopim sku:- :sku',
                ],
            ],
        ],
        'fields' => [
            'name'                        => 'Name',
            'description'                 => 'Description',
            'price'                       => 'Price',
            'weight'                      => 'Weight',
            'quantity'                    => 'Quantity',
            'inventory_tracked'           => 'Inventory Tracked',
            'allow_purchase_out_of_stock' => 'Allow Purchase Out of Stock',
            'vendor'                      => 'Vendor',
            'product_type'                => 'Product Type',
            'tags'                        => 'Tags',
            'barcode'                     => 'Barcode',
            'compare_at_price'            => 'Compare Price',
            'seo_title'                   => 'SEO Title',
            'seo_description'             => 'SEO Description',
            'handle'                      => 'Handle',
            'taxable'                     => 'Taxable',
            'inventory_cost'              => 'Cost per item',

        ],
        'exportmapping' => 'Attribute Mappings',
        'job'           => [
            'credentials'      => 'Shopify Credential',
            'channel'          => 'Channel',
            'currency'         => 'Currency',
            'productfilter'    => 'Product Filter (SKU)',
            'locale'           => 'Locale',
            'attribute-groups' => 'Attribute Groups',
        ],
        'metafield' => [
            'datagrid' => [
                'definitiontype'  => 'Used For',
                'attribute-label'  => 'Unopim Attribute',
                'definitionName'  => 'Definition name',
                'contentTypeName' => 'Type',
                'pin'             => 'Pin',
            ],
            'index'    => [
                'title'                     => 'Metafield definitions',
                'create'                    => 'Add definition',
                'definitiontype'            => 'Used For',
                'attribute'                 => 'UnoPim Attribute',
                'ContentTypeName'           => 'Type',
                'attributes'                => 'Definition Name',
                'urlvalidation'             => 'Validation',
                'urlvalidationdata'         => 'Values must be prefixed with: “HTTPS”, “HTTP”, “mailto:”, “sms:”, or “tel:”',
                'name_space_key'            => 'Namespace and key',
                'description'               => 'Description',
                'onevalue'                  => 'One Value',
                'listvalue'                 => 'List of Values',
                'validation'                => 'Validations',
                'maxvalue'                  => 'Max value',
                'adminFilterable'           => 'Filtering for products',
                'smartCollectionCondition'  => 'Smart collections',
                'storefronts'               => 'Storefronts access',
                'unit'                      => [
                    'minvalue' => '',
                    'maxvalue' => '',
                ],
            ],

            'type' => [
                'single_line_text_field' => 'Single line text',
                'color'                  => 'Color',
                'rating'                 => 'Rating',
                'url'                    => 'Url',
                'multi_line_text_field'  => 'Multi-line text',
                'json'                   => 'JSON',
                'boolean'                => 'True and False',
                'date'                   => 'Date',
                'number_decimal'         => 'Decimal',
                'number_integer'         => 'Number',
                'dimension'              => 'Dimension',
                'weight'                 => 'Weight',
                'volume'                 => 'Volume',
            ],

            'edit'     => [
                'title'           => 'Edit Metafield Definition',
                'back-btn'        => 'Back',
                'update'          => 'Update',
                'save'            => 'Save',
            ],
            'validation' => [
                'pin-limit'               => 'Pin limit reached, You can only have 20 pinned fields',
                'definition-exists'       => 'Definition already created in :type',
                'namespace-taken'         => 'Namespace and key are already taken for :type',
                'namespace-format'        => 'You need to use one period (.) to separate the namespace and key',
                'key-min-length'          => 'Key must be a minimum of 2 characters',
                'key-max-length'          => 'Key must be a maximum of 64 characters',
                'namespace-invalid-chars' => 'Namespace and key can only use letters, numbers, underscores, and dashes',
                'name-too-long'           => 'Name is too long (maximum is 255 characters)',
                'type-required'           => 'Type Field is required',
                'description-max-length'  => 'Description must be a maximum of 100 characters',
                'only-number'             => 'Only Number Allowed',
                'min-less-than-max'       => 'Validations contains an invalid value: min must be less than max',
                'rating-min-max-required' => 'Rating field must have both min and max values',
            ],
            'delete-success'      => 'Metafield Definition Deleted successfully',
            'update-success'      => 'MetaField Definition Updated successfully',
            'created'             => 'Create Metafield Definition successfully',
            'mass-delete-success' => 'Mass Delete Metafield Definition successfully',
        ],
    ],
];
