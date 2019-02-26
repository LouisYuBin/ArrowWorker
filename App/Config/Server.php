<?php

return [
    [
        'type'       => 'web',
        //port of listen
        'port'       => 9502,
        //number of worker process
        'workerNum'  => 8,
        //react thread number
        'reactorNum' => 4,
        //size of request queue
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        //max post data length
        'maxContentLength' => 20889600,
        //is enable request for static file
        'enableStaticHandler' => true,
        //static file path
        'documentRoot' => APP_PATH.'/Static/Web',
    ],
    [
        'type'       => 'web',
        //port of listen
        'port'       => 9503,
        //number of worker process
        'workerNum'  => 8,
        //react thread number
        'reactorNum' => 4,
        //size of request queue
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        //max post data length
        'maxContentLength' => 20889600,
        //is enable request for static file
        'enableStaticHandler' => true,
        //static file path
        'documentRoot' => APP_PATH.'/Static/Web',
    ],
    [
        'type'       => 'tcp',
        //port of listen
        'port'       => 9505,
        //number of worker process
        'workerNum'  => 8,
        //react thread number
        'reactorNum' => 4,
        //size of request queue
        'backlog'          => 20000,
        'pipeBufferSize'   => 1024*1024*200,
        'socketBufferSize' =>  1024*1024*200,
        'maxRequest'       => 100000,
        'maxCoroutine'     => 50000,
        //max post data length
        'maxContentLength' => 20889600,
        'handler'          => [
            'connect' => '',
            'receive' => '',
            'close'   => ''
        ]
    ],

];