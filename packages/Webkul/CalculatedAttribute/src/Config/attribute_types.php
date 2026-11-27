<?php

return [
    'calculated' => [
        'key'  => 'calculated',
        'name' => 'calculated_attribute::app.attribute.calculated',

        /**
         * Derived at read time, so there is nothing a user or an import could
         * fill in. Core reads this flag to keep the type out of the product
         * importer and out of the completeness requirements.
         */
        'fillable' => false,
    ],
];
