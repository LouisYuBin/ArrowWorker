<?php

return [
    'baseDir'  => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Log/',
    'process'  => 4,
    'bufSize'  => 10485760,     //100M
    'msgSize'  => 1048576,      //1M
    /*'tcp'      => [
        'host'     => '127.0.0.1',
        'port'     => 9666,
        'poolSize' => 10
    ],
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => 'r9&BsY$gKumtp7ic\n=pntpe?n6vtjchFijK',
        'queue'    => 'ArrowWorkerLog',
        'poolSize' => 10
    ],*/
];
