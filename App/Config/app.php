<?php
return[
    'RouterType' => 1,
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
        'enableGenerator' => true,
        'log'    => '/var/log/daemon.log',
        'level'  => 30719
        //1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
    ],
	'mongo' => [
        'username' => 'userName',
        'password' => '123456',
        'host'     => '127.0.0.1',
        'port'     => 27017,
        'dbName'   => 'dbName',
        /*
		 * update and release comments if you have backup node
        'secondary'=> [
            [
                'host' => '127.0.0.1',
                'port' => 27017
            ]
        ],
        */
    ],
    'rabbitmq' => [
        'host'     => '127.0.0.1',
        'port'     => '5672',
        'login'    => 'usernanme',
        'password' => 'password',
        'vhost'    => 'vhost',
        'exchange' => 'exchangeName',
        'route'    => 'routeName',
        'queue'    => 'queueName'
    ],
    'view' => [
  	'driver' => 'Smarty',
	'tplExt' => '.tpl'
    ],
    'user' => ['user']
];
