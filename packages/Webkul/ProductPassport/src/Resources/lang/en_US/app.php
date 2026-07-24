<?php

return [
    'type' => [
        'label' => 'Digital Product Passport',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'Product Passport',
            'info'     => 'Digital Product Passport publishing settings.',
            'settings' => [
                'title'                              => 'Product Passport Settings',
                'enabled'                            => 'Enabled',
                'enabled-hint'                       => 'Turn the Digital Product Passport feature on for this catalog. When off, the passport panel and grid are hidden.',
                'auto-publish'                       => 'Publish automatically on save',
                'auto-publish-hint'                  => 'Publish a passport version automatically whenever a product is saved and meets the completeness threshold. Leave off to publish manually.',
                'completeness-threshold'             => 'Completeness Threshold (%)',
                'completeness-threshold-hint'        => 'Minimum product completeness, as a percentage, required before a passport can be published for a locale.',
                'completeness-threshold-placeholder' => '80',
                'operator-name'                      => 'Economic Operator Name',
                'operator-name-hint'                 => 'Legal name of the manufacturer or responsible economic operator, shown on every public passport as required by the ESPR regulation.',
                'operator-name-placeholder'          => 'Acme Manufacturing GmbH',
                'operator-address'                   => 'Economic Operator Address',
                'operator-address-hint'              => 'Registered postal address of the economic operator, shown on the public passport for traceability.',
                'operator-address-placeholder'       => '123 Example Street, 10115 Berlin, Germany',
                'operator-eu-rep'                    => 'EU Authorised Representative',
                'operator-eu-rep-hint'               => 'Name and contact of the EU authorised representative, required when the manufacturer is established outside the EU.',
                'operator-eu-rep-placeholder'        => 'EU Rep Ltd, Dublin, Ireland',
                'support-url'                        => 'Support URL',
                'support-url-hint'                   => 'Public page where customers can find help or warranty information. Shown as a link on every passport.',
                'support-url-placeholder'            => 'https://example.com/support',
            ],
        ],
    ],

    'groups' => [
        'dpp' => 'Digital Product Passport',
    ],

    'attributes' => [
        'dpp_material_composition'      => 'Material Composition',
        'dpp_substances_of_concern'     => 'Substances of Concern',
        'dpp_recycled_content_pct'      => 'Recycled Content (%)',
        'dpp_carbon_footprint'          => 'Carbon Footprint',
        'dpp_energy_consumption'        => 'Energy Consumption',
        'dpp_durability_statement'      => 'Durability Statement',
        'dpp_repairability_score'       => 'Repairability Score',
        'dpp_spare_parts_availability'  => 'Spare Parts Availability',
        'dpp_care_instructions'         => 'Care Instructions',
        'dpp_disassembly_guide'         => 'Disassembly Guide',
        'dpp_manufacturer_name'         => 'Manufacturer Name',
        'dpp_manufacturing_site'        => 'Manufacturing Site',
        'dpp_country_of_origin'         => 'Country of Origin',
        'dpp_supply_chain_notes'        => 'Supply Chain Notes',
        'dpp_end_of_life_instructions'  => 'End-of-Life Instructions',
        'dpp_take_back_scheme'          => 'Take-Back Scheme',
        'dpp_declaration_of_conformity' => 'Declaration of Conformity',
        'dpp_test_reports'              => 'Test Reports',
        'dpp_certificates'              => 'Certificates',
        'dpp_gtin'                      => 'GTIN',
        'dpp_model_identifier'          => 'Model Identifier',
        'dpp_batch_identifier'          => 'Batch Identifier',
        'dpp_warranty_terms'            => 'Warranty Terms',
    ],

    'console' => [
        'install-attributes' => [
            'success' => 'Digital Product Passport attributes installed successfully.',
        ],
    ],

    'public' => [
        'title'         => 'Digital Product Passport',
        'badge'         => 'EU Digital Product Passport',
        'search-locale' => 'Search language',
        'sections'      => [
            'passport' => 'Product Passport',
        ],
        'identifier' => [
            'title'        => 'Identification',
            'gtin'         => 'GTIN',
            'model'        => 'Model',
            'batch'        => 'Batch',
            'not-provided' => 'Not provided',
        ],
        'operator' => [
            'title' => 'Economic Operator',
        ],
        'documents' => [
            'title' => 'Documents',
        ],
    ],

    'publications' => [
        'not-found'      => 'No passport found for id :id.',
        'index'          => [
            'title'           => 'Digital Product Passports',
            'disabled-notice' => 'Passport publishing is currently disabled. Existing passports are shown below for management (view and withdraw).',
        ],
        'datagrid' => [
            'uuid'           => 'UUID',
            'sku'            => 'SKU',
            'channel'        => 'Channel',
            'status'         => 'Status',
            'live-locales'   => 'Live Locales',
            'last-published' => 'Last Published',
            'withdraw'       => 'Withdraw',
            'mass-publish'   => 'Publish selected',
        ],
        'publish-queued'      => 'Passport publishing has been queued.',
        'bulk-publish-queued' => 'Passport publishing has been queued for the selected passports.',
        'withdrawn'           => 'Passport withdrawn successfully.',
        'mass-publish'        => [
            'action' => 'Publish Digital Product Passport',
            'queued' => 'Passport publishing queued for :count product(s).',
        ],
    ],

    'acl' => [
        'passports' => [
            'index'    => 'Passports',
            'view'     => 'View',
            'publish'  => 'Publish',
            'withdraw' => 'Withdraw',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'passports' => [
                        'name' => 'Passports',
                    ],
                ],
            ],
        ],
    ],

    'catalog' => [
        'products' => [
            'edit' => [
                'passport' => [
                    'title'                => 'Digital Product Passport',
                    'publishing-disabled'  => 'Passport publishing is disabled for this channel.',
                    'locale'               => 'Locale',
                    'version'              => 'Version',
                    'published-at'         => 'Published At',
                    'missing-fields'       => 'Missing Fields',
                    'not-published'        => 'Not published',
                    'unscored'             => 'Not scored',
                    'publish'              => 'Publish',
                    'republish'            => 'Re-publish',
                    'publish-all'          => 'Publish all locales',
                    'auto-publish-on'      => 'Auto-publish is on — passports publish automatically when the product is saved and meets the completeness threshold. Use the buttons to publish now.',
                    'auto-publish-off'     => 'Manual publishing — use the buttons to publish this product’s passport for each locale.',
                    'publishing'           => 'Publishing…',
                    'queued'               => 'Queued',
                    'copy-operator-link'   => 'Copy operator link',
                    'copy-authority-link'  => 'Copy authority link',
                    'link-copied'          => 'Link copied',
                    'download-qr'          => 'Download QR code',
                ],
            ],
        ],
    ],
    'mapping' => [
        'title'         => 'Passport Field Mapping',
        'info'          => 'Source each passport field from an attribute you already maintain. Leave a field unmapped to fall back to its dedicated passport attribute.',
        'menu'          => 'Field Mapping',
        'field'         => 'Passport Field',
        'source'        => 'Source Attribute',
        'select-source' => 'Use the passport attribute',
        'save-btn'      => 'Save Mapping',
        'type-mismatch' => 'The selected source is not compatible with this passport field\'s type.',
        'saved'         => 'Field mapping saved successfully.',
    ],

    'validation' => [
        'gtin' => 'The :attribute must be a valid GTIN (8, 12, 13, or 14 digits with a correct check digit).',
    ],
];
