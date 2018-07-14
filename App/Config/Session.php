<?php

return [
    //RedisSession:redis存储, MemcachedSession:memcache存储
    'handler'  => 'RedisSession',
    //redis/memcache地址，handler为RedisSession或MemcacheSession时使用
    'host'	   => '127.0.0.1',
    //redis/memcache端口，handler为RedisSession或MemcacheSession时使用
    'port'	   => 6379,
    //用户名，对memcached有效
    'userName' => '',
    //密码，handler为RedisSession/memcached时使用
    'password' => 'louis',
    //session超时时间
    'timeout'  => 3600,
    //客户端使用cookie做session存储时使用
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