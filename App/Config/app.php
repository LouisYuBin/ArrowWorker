<?php
return[
    //web应用路由类型 1:get 2:uri
    'RouterType' => 1,
    //数据库连接
    'db' => [
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
            'dbName'   => 'erp',
            //端口
            'port'     => 3306,
        ],
        //从库配置,多个，配置同主库配置
        'slave' => [
            [
                'host'     => '127.0.0.1',
                'userName' => 'root',
                'password' => 'louis',
                'dbName'   => 'erp',
                'port'     => 3306,
            ]
        ],
    ],
    //缓存配置
    'cache' => [
        //驱动类型
        'driver'   => 'Redis',
        //地址
        'host'     => '127.0.0.1',
        //端口
        'port'     => 6379,
        //密码
        'password' => 'carlt_louis_2017_03_17'
    ],
    //常驻服务配置
    'daemon' =>[
        //驱动类型
        'driver' => 'ArrowDaemon',
        //进程名称
        'name'   => 'Index',
        //进程id文件名称
        'pid'    => 'ArrowWorker',
        //用户名
        'user'   => 'root',
        //线程数（在使用多线程模式下有效，依赖pthread扩展）
        'thread' => 6,
        //是否启用协成（不建议使用，调度损耗较大）
        'enableGenerator' => false,
        //日志文件路径（路路径必须存在，且对应文件夹要有相应权限）
        'log'    => '/var/log/ArrowWorker.log',
        //日志等级，1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
        'level'  => 30719
    ],
    //mongodb配置
	'mongo' => [
	    //用户名
        'username' => 'admin',
        //密码
        'password' => 'admin',
        //地址
        'host'     => '127.0.0.1',
        //端口
        'port'     => 27017,
        //数据库名
        'dbName'   => 'admin',
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
    //rabbitMq配置
    'rabbitmq' => [
        //地址
        'host'     => '127.0.0.1',
        //端口
        'port'     => '5672',
        //用户名
        'login'    => 'usernanme',
        //密码
        'password' => 'password',
        //虚拟机
        'vhost'    => 'vhost',
        //交换机
        'exchange' => 'exchangeName',
        //路由
        'route'    => 'routeName',
        //队列名
        'queue'    => 'queueName'
    ],
    //渲染器
    'view' => [
     //驱动类型
  	'driver' => 'Smarty',
     //模板文件后缀
	'tplExt' => '.tpl'
    ],
    //swoole web配置
    'swoole' => [
        //端口
        'port'      => 9502,
        //工作进程数
        'workerNum' => 10,
        //是否常驻
        'daemonize' => false,
        //请求队列长度
        'backlog'   => 2000
    ],
    //应用其他配置文件
    'user' => ['user']
];
