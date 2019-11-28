<?php
use \ArrowWorker\Server\Server;

return [
    [
        'type'             => Server::TYPE_HTTP,
        'host'             => '0.0.0.0',
        'port'             => 4433,
        'workerNum'        => 1,
        'reactorNum'       => 2,
        'backlog'          => 2000,
        'user'             => 'www',
        'group'            => 'www',
        'pipeBufferSize'   => 1024*1024*20,
        'socketBufferSize' =>  1024*1024*20,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'isEnableStatic'   => true,
        'isEnableCORS'     => true,
        'isEnableHttp2'    => true,
        'sslCertFile'      => APP_PATH.'/Runtime/Ssl/dugujiujian.net_bundle.crt',
        'sslKeyFile'       => APP_PATH.'/Runtime/Ssl/dugujiujian.net.key',
        'documentRoot'     => APP_PATH.'/Static/Web',
        '404'              => APP_PATH.'/Static/Web/404.html',
        'components' => [
            'db' => [
                'default' => 2
            ],
            'cache' => [
                'default' => 2
            ],
           'tcp_client' => [
                'default' => 3,
                'conner'  => 2
            ],
            'ws_client' => [
                'default' => 2
            ]
        ]
    ],
    [
        'type'             => Server::TYPE_WEBSOCKET,
        'host'             => '0.0.0.0',
        'port'             => 8081,
        'workerNum'        => 1,
        'reactorNum'       => 1,
        'backlog'          => 2000,
        'user'             => 'www',
        'group'            => 'www',
        'pipeBufferSize'   => 1024*1024*20,
        'socketBufferSize' => 1024*1024*20,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'isEnableStatic'   => true,
        'isEnableCORS'     => true,
        'documentRoot' => APP_PATH.'/Static/Web',
        '404'          => APP_PATH.'/Static/Web/404.html',
        'handler'      => [
            'open'    => 'Demo\\WebSocket::Open',
            'message' => 'Demo\\WebSocket::Message',
            'close'   => 'Demo\\WebSocket::Close'
        ],
        'components' => [
            'db' => [
                'default' => 5
            ],
            'cache' => [
                'default' => 2
            ],
            'tcp_client' => [
                'default' => 3,
                'conner'  => 2
            ],
            'ws_client' => [
                'default' => 2
            ]
        ]
    ],
    [
        'type'             => Server::TYPE_TCP,
        'host'             => '0.0.0.0',
        'port'             => 9505,
        'workerNum'        => 1,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'user'             => 'www',
        'group'            => 'www',
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'heartbeatCheckInterval' => 30,
        'heartbeatIdleTime' => 60,
        'openEofCheck'      => false,
        'packageEof'        => '\r\n',
        'openEofSplit'      => false,
        'handler'           => [
            'connect' => 'Demo\\Tcp::Connect',
            'receive' => 'Demo\\Tcp::Receive',
            'close'   => 'Demo\\Tcp::Close'
        ],
        'components' => [
            'db' => [
                'default' => 2
            ],
            'cache' => [
                'default' => 2
            ],
            'tcp_client' => [
                'default' => 3,
                'conner'  => 2
            ],
            'ws_client' => [
                'default' => 2
            ]
        ]
    ],
/*
    [
        'type'             => Server::SERVER_UDP,
        'host'             => '0.0.0.0',
        'port'             => 9506,
        'workerNum'        => 8,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'handler'          => [
            'connect' => 'Tcp::Connect',
            'receive' => 'Tcp::Receive',
            'close'   => 'Tcp::Close'
        ]
    ]
*/

];
