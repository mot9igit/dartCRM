<?php

return [
    'dartCRM' => [
        'file' => 'dartcrm',
        'description' => 'dartCRM hook for FormIt',
        'properties' => [
            'name' => [
                'type' => 'textfield',
                'value' => 'Заявка с сайта',
            ],
            'pipeline_id' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'status_new' => [
                'type' => 'textfield',
                'value' => '',
            ]
        ],
    ],
];