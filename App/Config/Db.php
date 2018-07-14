<?php

return [
    'app' => [
        //驱动类型
        'driver'      => 'Mysqli',
        //是否进行主从分离 1支持，2不支持
        'seperate'    => 0,
        //编码
        'charset'     => 'utf8',
        //主库配置
        'master'      => [
            //地址
            'host'     => '127.0.0.1',
            //用户名
            'userName' => 'root',
            //密码
            'password' => 'louis',
            //数据库名
            'dbName'   => 'ArrowWorker',
            //端口
            'port'     => 3306,
        ],
        //从库配置,多个，配置同主库配置
        'slave' => [
            [
                'host'     => '127.0.0.1',
                'userName' => 'root',
                'password' => 'louis',
                'dbName'   => 'test',
                'port'     => 3306,
            ]
        ],
    ],
];