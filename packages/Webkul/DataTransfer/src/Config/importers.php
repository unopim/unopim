<?php

return [
    'products' => [
        'title'            => 'data_transfer::app.importers.products.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Product\Importer',
        'sample_path'      => 'data-transfer/samples/products.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\ProductJobValidator',
        'has_file_options' => true,
    ],

    'categories' => [
        'title'            => 'data_transfer::app.importers.categories.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Category\Importer',
        'sample_path'      => 'data-transfer/samples/categories.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\CategoryJobValidator',
        'has_file_options' => true,
    ],

    'attributes' => [
        'title'            => 'data_transfer::app.importers.attributes.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\Attribute\Importer',
        'sample_path'      => 'data-transfer/samples/attributes.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeJobValidator',
        'has_file_options' => true,
    ],

    'attribute-groups' => [
        'title'            => 'data_transfer::app.importers.attribute-groups.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeGroup\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-groups.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeGroupJobValidator',
        'has_file_options' => true,
    ],

    'attribute-families' => [
        'title'            => 'data_transfer::app.importers.attribute-families.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeFamily\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-families.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeFamilyJobValidator',
        'has_file_options' => true,
    ],

    'attribute-options' => [
        'title'            => 'data_transfer::app.importers.attribute-options.title',
        'importer'         => 'Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer',
        'sample_path'      => 'data-transfer/samples/attribute-options.csv',
        'validator'        => 'Webkul\DataTransfer\Validators\JobInstances\Import\AttributeOptionJobValidator',
        'has_file_options' => true,
    ],
];
