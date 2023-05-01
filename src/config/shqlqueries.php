<?php
return [
    'addTask' => [
        'model' => \App\Models\Core\Task::class,
        'type'=>'create',
        'forceFill' => [
            'user_id' => '{current_user_id}'
        ],
        'log'=>[
            'slug'=>'task-added',
            'log'=>'Added task #{id} {name}'
        ],
        'notification'=>[
            'slug'=>'task-added'
        ]
    ],
    'updateTask' => [
        'model' => \App\Models\Core\Task::class,
        'type'=>'update',
        'where' => [
            'user_id' => '{current_user_id}'
        ],
        'log'=>[
            'slug'=>'task-updated',
            'log'=>'Updated task #{id} {name}'
        ]
    ]
];
