<?php

return [
    'type'    => 'File',
    'baseDir' => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Log/',
    'bufSize' => 104857600,     //100M
    'msgSize' => 1048576,      //1M
    'ip'       => '127.0.0.1',
    'port'     => 6379,
    'userName' => 'root',
    'password' => 'louis',
    'queue'    => 'ArrowWorkerLog',
    'timeZone' => 'Asia/Shanghai'

];
