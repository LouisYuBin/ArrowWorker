<?php

use App\Controller\Admin\Index;

return [
    'ws.com, web.com, arrow.com' => [
        '/'         => [
            'get'    => APP_PATH . '/Static/Web/index.html',
            'put'    => [
                Index::class,
                'put',
            ],
            'post'   => [
                Index::class,
                'post',
            ],
            'delete' => [
                Index::class,
                'delete',
            ],
        ],
        '/user/:id' => [
            'get'    => [
                Index::class,
                'get',
            ],
            'put'    => [
                Index::class,
                'put',
            ],
            'post'   => [
                Index::class,
                'post',
            ],
            'delete' => [
                Index::class,
                'delete',
            ],
        ],
        '/manager'  => [
            '/user/:id'                                                           => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/product/:id'                                                        => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/product/:id/status/:status'                                         => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/product/:id/delete/:delete'                                         => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/category'                                                           => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/attribute/:id'                                                      => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
            '/attribute/:id/status/:status/startDate/:startDate/endDate/:endDate' => [
                'get'    => [
                    Index::class,
                    'get',
                ],
                'put'    => [
                    Index::class,
                    'put',
                ],
                'post'   => [
                    Index::class,
                    'post',
                ],
                'delete' => [
                    Index::class,
                    'delete',
                ],
            ],
        ],
    ],
];
