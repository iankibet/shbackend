<?php

return [
    'api_middleware'=>env('SH_API_MIDDLEWARE','auth:sanctum'),
    'route_overrides' => [
        'sh_departments' => [
            'departments_controller' => null,
        ],
    ],
];
