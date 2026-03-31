<?php

return [
    'api_middleware'=>env('SH_API_MIDDLEWARE','auth:sanctum'),
    'controller_overrides' => [
        'departments_controller' => null,
    ],
];
