<?php

use App\Controller\Demo\Index;
use App\Middleware\Authorization;

return [
    'http'   => [
        'api.dugujiujian.com, ws.com, web.com, arrow.com' => [
            '/manager/*' => [
                '*' => [
                    Authorization::class,
                ],
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
            'index',
            [
                Authorization::class,
            ],
        ],
        [
            Index::class,
            'index',
            [
                Authorization::class,
            ],
        ],
    ],
];
