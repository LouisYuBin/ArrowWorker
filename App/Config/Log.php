<?php

return [
    'baseDir'  => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Log/',
    'bufSize'  => 104857600,     //100M
    'msgSize'  => 1048576,      //1M
    'chanSize' => 0,
    'type'     => [
        'file',
        'tcp',
        //'redis'
    ],
    'tcp'      => [
        'host' => '127.0.0.1',
        'port' => 9666,
    ],
    'redis'    => [
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => 'louis',
        'queue'    => 'ArrowWorkerLog',
    ],
    'timeZone' => 'Asia/Shanghai'
];
