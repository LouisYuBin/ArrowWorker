<?php

return [
    '127.0.0.1:4433, 127.0.0.1:8081' => [
        '/' => [
            'get'    => 'Admin\\Index@get',
            'put'    => 'Admin\\Index@put',
            'post'   => 'Admin\\Index@post',
            'delete' => 'Admin\\Index@delete'
        ],
        '/user/:id' => [
            'get'    => 'Admin\\Index@get',
            'put'    => 'Admin\\Index@put',
            'post'   => 'Admin\\Index@post',
            'delete' => 'Admin\\Index@delete'
        ],
        '/manager' =>  [
            '/user/:id' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/product/:id' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/product/:id/status/:status' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/product/:id/delete/:delete' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/category' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/attribute/:id' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ],
            '/attribute/:id/status/:status/startDate/:startDate/endDate/:endDate' => [
                'get'    => 'Admin\\Index@get',
                'put'    => 'Admin\\Index@put',
                'post'   => 'Admin\\Index@post',
                'delete' => 'Admin\\Index@delete'
            ]
        ]
    ]
];
