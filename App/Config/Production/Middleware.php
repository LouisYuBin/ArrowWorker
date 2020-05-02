<?php

use App\Controller\Demo\Index;
use App\Middleware\Authorization;

return [
    'http'   => [
        'api.dugujiujian.com' => [
            '/manager/*' => [
                '*',
                Authorization::class,
            ],
        ],
    ],
    'class'  => [
        Index::class => [
            Authorization::class,
        ],

    ],
    'method' => [
        [
            Index::class,
            'Index',
            Authorization::class,
        ],
    ],
];
