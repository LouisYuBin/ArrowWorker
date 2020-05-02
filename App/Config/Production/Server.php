<?php

return [
    [
        'type'             => 'Http',
        'host'             => '0.0.0.0',
        'port'             => 443,
        'workerNum'        => 1,
        'reactorNum'       => 1,
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
        'isEnableHttp2'    => false,
        'sslCertFile'      => APP_PATH . '/Runtime/Ssl/dugujiujian.net_bundle.crt',
        'sslKeyFile'       => APP_PATH . '/Runtime/Ssl/dugujiujian.net.key',
        'documentRoot'     => APP_PATH . '/Static/Web',
        '404'              => APP_PATH . '/Static/Web/404.html',
        'components'       => [
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