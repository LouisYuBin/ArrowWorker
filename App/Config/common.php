<?php
return[
    'db' => [
        'driver'      => 'Mysqli',
        'seperate'    => 0,
        'charset'     => 'utf8',
        'master'      => [
            'host'     => '127.0.0.1',
            'userName' => 'userName',
            'password' => 'password',
            'dbName'   => 'dbName',
            'port'     => 3306,
        ],
        'slave' => [
            [
                'host'     => '127.0.0.1',
                'userName' => 'userName',
                'password' => 'password',
                'dbName'   => 'dbName',
                'port'     => 3306,
            ]
        ],
    ],
    'cache' => [
        'driver'   => 'Redis',
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'password' => 'password'
    ],
    'daemon' =>[
        'driver' => 'ArrowDaemon',
        'name'   => 'Index',
        'pid'    => 'ArrowWorker',
        'user'   => 'root',
        'thread' => 6,
        'log'    => '/var/log/daemon.log',
        'level'  => 30719
        //1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
    ],
    'view' => [
  	'driver' => 'Smarty',
	'tplExt' => '.tpl'
    ],
    'user' => ['user']
];
