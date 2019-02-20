<?php

return [
    //port of listen
    'port'      => 9502,
    //number of worker process
    'workerNum' => 8,
    //react thread number
    'reactorNum' => 4,
    //size of request queue
    'backlog'          => 20000,
    'pipeBufferSize'   => 1024*1024*200,
    'socketBufferSize' =>  1024*1024*200,
    'maxRequest'       => 20000,
    'maxCoroutine'     => 50000,
    //max post data length
    'maxContentLength' => 20889600,
    //is enable request for static file
    'enableStaticHandler' => true,
    //static file path
    'documentRoot' => '/home/louis/data/github/ArrowWorker/App/Runtime/Static',

];