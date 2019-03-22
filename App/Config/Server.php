<?php

return [
    [
        'type'             => 'web',
        'port'             => 9502,
        'workerNum'        => 8,
        'reactorNum'       => 4,
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        'maxContentLength' => 20889600,
        'enableStaticHandler' => true,
        'documentRoot' => APP_PATH.'/Static/Web',
    ],
    [
        'type'             => 'webSocket',
        'port'             => 9503,
        'workerNum'        => 8,
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
        'port'             => 9505,
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
    ],
/*
    [
        'type'             => 'udp',
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