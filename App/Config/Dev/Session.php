<?php

return [
    'handler'  => 'RedisSession',
    'host'	   => '127.0.0.1',
    'port'	   => 6379,
    'userName' => '',
    'password' => 'louis',
    'timeout'  => 3600,
    'cookie'   => [
        //超时时间
        'expire' => 36000,
        //所属路径
        'path' => '/',
        //所属域名
        'domain' => '',
        //是否只允许针对https协议有效
        'secure' => false,
        //是否只允许http协议修改
        'httponly' => true
    ]
];