<?php
return[
    'db_config' => [                         //数据库配置
        'driver'      => 'mysqli',           //驱动
        'seperate'    => 0,                  //读写分离
        'charset'     => 'utf8',             //编码
        'master'      => [                   //主库配置
            'host'     => '127.0.0.1',
            'userName' => 'root',
            'password' => 'louis5310',
            'dbName'   => 'nova_monitor',
            'port'     => 3306,
        ],
        'slave' => [                          //从库配置
            [
                'host'     => '127.0.0.1',
                'userName' => 'root',
                'password' => 'louis5310',
                'dbName'   => 'nova_monitor',
                'port'     => 3306,
            ],
            [
                'host'     => '127.0.0.1',
                'userName' => 'root',
                'password' => 'louis5310',
                'dbName'   => 'nova_monitor',
                'port'     => 3306,
            ]
        ],
    ],
    'cache' => [                              //缓存配置
        'driver'   => 'redis',
        'host'     => '127.0.0.1',
        'userName' => 'admin',
        'password' => '1234564',
        'port'     => 3306,
        'dbName'   => 'admin'
    ],
    'config' => ['user','product']            //需要加载的用户配置文件
];