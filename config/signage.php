<?php

return [
    'api_providers' => [
        'open-meteo' => [
            'name' => 'Open-Meteo Weather',
            'base_url' => 'https://api.open-meteo.com',
            'healthcheck_url' => 'https://api.open-meteo.com/v1/forecast?latitude=-6.221441&longitude=106.78094&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta',
            'auth_type' => 'none',
        ],
        'jsonplaceholder' => [
            'name' => 'JSONPlaceholder Demo',
            'base_url' => 'https://jsonplaceholder.typicode.com',
            'healthcheck_url' => 'https://jsonplaceholder.typicode.com/todos/1',
            'auth_type' => 'none',
        ],
    ],
    'api_allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SIGNAGE_API_ALLOWED_HOSTS', '')),
    ))),
];
