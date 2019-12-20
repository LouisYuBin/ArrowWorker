<?php

return [
    'baseDir'  => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Log/',
    'process'  => 1,
    'bufSize'  => 104857600,     //100M
    'msgSize'  => 1048576,      //1M
    /*'tcp'      => [
        'host' => '127.0.0.1',
        'port' => 9666,
    ],
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => 'louis',
        'queue'    => 'ArrowWorkerLog',
    ],*/
];
