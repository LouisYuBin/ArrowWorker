<?php

return [
    [
        'type'             => 'web',
        'host'             => '0.0.0.0',
        'port'             => 8088,
        'workerNum'        => 4,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'enableStaticHandler' => true,
        'sslCertFile'      => APP_PATH.'/Runtime/Ssl/dugujiujian.net_bundle.crt',
        'sslKeyFile'       => APP_PATH.'/Runtime/Ssl/dugujiujian.net.key',
        'documentRoot'     => APP_PATH.'/Static/Web',
        ''
    ],
    [
        'type'             => 'webSocket',
        'host'             => '0.0.0.0',
        'port'             => 8089,
        'workerNum'        => 4,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'enableStaticHandler' => true,
        'documentRoot' => APP_PATH.'/Static/Web',
        'handler'      => [
            'open'    => 'WebSocket::Open',
            'message' => 'WebSocket::Message',
            'close'   => 'WebSocket::Close'
        ]
    ],
    [
        'type'             => 'tcp',
        'host'             => '0.0.0.0',
        'port'             => 9505,
        'workerNum'        => 4,
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
    ],
/*
    [
        'type'             => 'udp',
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