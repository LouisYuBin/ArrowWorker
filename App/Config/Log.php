<?php

return [
    'type'    => 'File',
    'baseDir' => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.DIRECTORY_SEPARATOR.'Log/',
    'bufSize' => 10485760,
    'fileSize' => 10485760,
    'timeZone' => 'PRC',
    'ip'       => '127.0.0.1',
    'port'     => 6379,
    'userName' => 'root',
    'password' => 'louis',
    'queue'    => 'ArrowWorkerLog'

];
