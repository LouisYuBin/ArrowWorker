<?php

return [
    '127.0.0.1:8081' => [
        'handler'  => 'redis',
        'host'	   => '127.0.0.1',
        'port'	   => 6379,
        'password' => 'louis',
        'timeout'  => -1,
        'key'      => 'token',
        'keyFrom'  => 'get', //get, post, cookie
    ]

];