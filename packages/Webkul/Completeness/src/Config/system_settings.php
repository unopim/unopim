<?php

use Webkul\Completeness\Services\ScoreThresholds;
use Webkul\Completeness\Validators\ScoreThresholdsValidator;

return [
    [
        'key'       => 'system.completeness',
        'name'      => 'completeness::app.configuration.score-thresholds.title',
        'info'      => 'completeness::app.configuration.score-thresholds.info',
        'icon'      => 'icon-activity',
        'acl'       => 'configuration.system_settings.completeness',
        'validator' => ScoreThresholdsValidator::class,
        'sort'      => 6,
        'fields'    => [
            [
                'name'          => 'green_from',
                'title'         => 'completeness::app.configuration.score-thresholds.green-from',
                'type'          => 'number',
                'info'          => 'completeness::app.configuration.score-thresholds.green-from-info',
                'default_value' => (string) ScoreThresholds::DEFAULT_GREEN_FROM,
                'validation'    => 'required|numeric|min:0|max_value:100',
            ],
            [
                'name'          => 'red_below',
                'title'         => 'completeness::app.configuration.score-thresholds.red-below',
                'type'          => 'number',
                'info'          => 'completeness::app.configuration.score-thresholds.red-below-info',
                'default_value' => (string) ScoreThresholds::DEFAULT_RED_BELOW,
                'validation'    => 'required|numeric|min:0|max_value:100',
            ],
        ],
    ],
];
