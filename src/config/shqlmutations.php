<?php
return [
    'addTask' => [
        'model' => \App\Models\Core\Task::class,
        'forceFill' => [
            'user_id' => '{current_user_id}'
        ]
    ],

];
