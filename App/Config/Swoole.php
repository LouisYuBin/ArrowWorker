<?php

return [
    //web server
    'http' => [
        //port of listen
        'port'      => 9502,
        //number of worker process
        'workerNum' => 8,
        //size of request queue
        'backlog'   => 2000,
        //max post data length
        'maxContentLength' => 20889600
    ],
];