<?php

return [
    'api_middleware'=>env('SH_API_MIDDLEWARE','auth:sanctum'),
    'route_overrides' => [
        'sh_departments' => [
            'department' => [
                'list_all_modules' => null,
                'get_module_permissions' => null,
            ],
        ],
    ],
];
