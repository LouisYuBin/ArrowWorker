<?php

return [
    //port of listen
    'port'      => 9502,
    //number of worker process
    'workerNum' => 8,
    //react thread number
    'reactor_num' => 4,
    //size of request queue
    'backlog'   => 20000,
    'pipe_buffer_size' => 1024*1024*100,
    'socket_buffer_size' =>  1024*1024*100,
    'max_coroutine' => 5000,
    //max post data length
    'maxContentLength' => 20889600,
    //is enable request for static file
    'enableStaticHandler' => true,
    //static file path
    'documentRoot' => '/home/louis/data/github/ArrowWorker/App/Runtime/Static',

];