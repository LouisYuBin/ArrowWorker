<?php

return [
    //port of listen
    'port'      => 9502,
    //number of worker process
    'workerNum' => 8,
    //size of request queue
    'backlog'   => 2000,
    //max post data length
    'maxContentLength' => 20889600,
    //is enable request for static file
    'enableStaticHandler' => true,
    //static file path
    'documentRoot' => '/home/louis/data/github/ArrowWorker/App/Runtime/Static',

];