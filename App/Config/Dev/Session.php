<?php

return [
    'web.com, ws.com' => [
        'handler'   => 'redis',
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'password'  => 'louis',
        'poolSize'  => '100',
        'timeout'   => -1,
        'tokenFrom' => 'get',
        //get, post, cookie
        'tokenKey'  => 'token',
    ],

];