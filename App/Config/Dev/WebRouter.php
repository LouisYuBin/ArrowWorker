<?php


return [
    'ws.com, web.com, arrow.com' => [
        '/' => [
            'get'    => APP_PATH.'/Static/Web/index.html',
            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
        ],
        '/user/:id' => [
            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
        ],
        '/manager' =>  [
            '/user/:id' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/product/:id' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/product/:id/status/:status' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/product/:id/delete/:delete' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/category' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/attribute/:id' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ],
            '/attribute/:id/status/:status/startDate/:startDate/endDate/:endDate' => [
	            'get'    => [ \App\Controller\Admin\Index::class, 'get'],
	            'put'    => [ \App\Controller\Admin\Index::class, 'put'],
	            'post'   => [ \App\Controller\Admin\Index::class, 'post'],
	            'delete' => [ \App\Controller\Admin\Index::class, 'delete']
            ]
        ]
    ]
];
