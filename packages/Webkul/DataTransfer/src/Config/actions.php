<?php

return [
    'actions' => [
        [
            'id'    => 'append',
            'title' => 'admin::app.settings.data-transfer.imports.create.create-update',
        ],
        [
            'id'    => 'delete',
            'title' => 'admin::app.settings.data-transfer.imports.create.delete',
        ],
    ],
    'validation_strategy' => [
        [
            'id'    => 'stop-on-errors',
            'title' => 'admin::app.settings.data-transfer.imports.create.stop-on-errors',
        ],
        [
            'id'    => 'skip-erros',
            'title' => 'admin::app.settings.data-transfer.imports.create.skip-errors',
        ],
    ],

];
