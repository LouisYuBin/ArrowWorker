<?php

return [
    'ws.com, web.com, arrow.com, 127.0.0.1:4433, 127.0.0.1:8081' => [
        '/'                                                                           => [
            'get'    => APP_PATH . '/Static/Web/index.html',
            'put'    => 'Admin\\Index@put',
            'post'   => 'Admin\\Index@post',
            'delete' => 'Admin\\Index@delete',
        ],
        '/user/:id'                                                                   => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/user/:id'                                                           => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/product/:id'                                                        => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/product/:id/status/:status'                                         => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/product/:id/delete/:delete'                                         => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/category'                                                           => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/attribute/:id'                                                      => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
        '/manager/attribute/:id/status/:status/startDate/:startDate/endDate/:endDate' => [
            'get'    => [
                App\Controller\Admin\Index::class,
                'get',
            ],
            'put'    => [
                App\Controller\Admin\Index::class,
                'put',
            ],
            'post'   => [
                App\Controller\Admin\Index::class,
                'post',
            ],
            'delete' => [
                App\Controller\Admin\Index::class,
                'delete',
            ],
        ],
    ],
];
