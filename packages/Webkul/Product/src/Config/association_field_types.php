<?php

/**
 * Field types offered when defining custom fields on an association type.
 *
 * Scoped to `text` and `boolean` for now; the storage, validation and rendering
 * layers already understand the richer types, so re-enabling one is a matter of
 * merging its entry back into this config from an extending package.
 */
return [
    'text' => [
        'key'  => 'text',
        'name' => 'admin::app.catalog.attributes.create.text',
    ],

    'boolean' => [
        'key'  => 'boolean',
        'name' => 'admin::app.catalog.attributes.create.boolean',
    ],
];
