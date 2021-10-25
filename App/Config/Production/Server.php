<?php

return [
    [
        'type'               => 'Http',
        'host'               => '0.0.0.0',
        'port'               => 443,
        'worker_num'         => 1,
        'reactor_num'        => 1,
        'backlog'            => 20000,
        'user'               => 'www',
        'group'              => 'www',
        'pipe_buffer_size'   => 1024 * 1024 * 200,
        'socket_buffer_size' => 1024 * 1024 * 200,
        'max_request'        => 100000,
        'max_coroutine'      => 50000,
        'max_content_length' => 20889600,
        'is_enable_static'   => true,
        'is_enable_CORS'     => true,
        'is_enable_http2'    => false,
        'ssl_cert_file'      => APP_PATH . '/Runtime/Ssl/dugujiujian.net_bundle.crt',
        'ssl_key_file'       => APP_PATH . '/Runtime/Ssl/dugujiujian.net.key',
        'document_root'      => APP_PATH . '/Static/Web',
        '404'                => APP_PATH . '/Static/Web/404.html',
        'components'         => [
            'db'    => [
                'default' => 100,
            ],
            'cache' => [
                'default' => 100,
            ],
            /*'tcp_client' => [
                 'default' => 3,
                 'conner'  => 2
             ],
             'ws_client' => [
                 'default' => 2
             ]*/
        ],
    ],
    [
        'type'             => 'Ws',
        'host'             => '0.0.0.0',
        'port'             => 80,
        'workerNum'        => 1,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'user'             => 'www',
        'group'            => 'www',
        'pipeBufferSize'   => 1024 * 1024 * 200,
        'socketBufferSize' => 1024 * 1024 * 200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'isEnableStatic'   => true,
        'isEnableCORS'     => true,
        'documentRoot'     => APP_PATH . '/Static/Web',
        '404'              => APP_PATH . '/Static/Web/404.html',
        'callback'         => \App\Controller\Demo\WebSocket::class,
        'components'       => [
            'db'    => [
                'default' => 50,
            ],
            'cache' => [
                'default' => 50,
            ],
        ],
    ],
    [
        'type'                   => 'Tcp',
        'host'                   => '0.0.0.0',
        'port'                   => 9505,
        'workerNum'              => 1,
        'reactorNum'             => 4,
        'backlog'                => 20000,
        'user'                   => 'www',
        'group'                  => 'www',
        'pipeBufferSize'         => 1024 * 1024 * 200,
        'socketBufferSize'       => 1024 * 1024 * 200,
        'maxRequest'             => 100000,
        'maxCoroutine'           => 50000,
        'maxContentLength'       => 20889600,
        'heartbeatCheckInterval' => 30,
        'heartbeatIdleTime'      => 60,
        'openEofCheck'           => false,
        'packageEof'             => '\r\n',
        'openEofSplit'           => false,
        'callback'               => \App\Controller\Demo\Tcp::class,
        'components'             => [
            'db'    => [
                'default' => 20,
            ],
            'cache' => [
                'default' => 20,
            ],
        ],
    ],

];