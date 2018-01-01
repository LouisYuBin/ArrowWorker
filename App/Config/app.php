<?php
$app = [
    //应用其他配置文件
    'user' => ['user']
];

//数据库连接
$app['Db'] = [
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

//缓存设置
$app['Cache'] = [
    'app' => [
        //驱动类型
        'driver'   => 'Redis',
        //地址
        'host'     => '127.0.0.1',
        //端口
        'port'     => 6379,
        //密码
        'password' => 'louis'
    ]
];

//消息通信（管道）
$app['Channel'] = [
    'app' => [
        //驱动类型
        'driver' => 'Queue',
        //映射路径
        'path'   => '/home/louis/data/github/ArrowWorker/App/Runtime/app.queue',
        //最大读取长度
        'size'   => 128,
		//队列占用byte大小设置
		'length' => 10240000
    ],
    'arrow' => [
        //驱动类型
        'driver' => 'Queue',
        //路径
        'path'   => '/home/louis/github/ArrowWorker/App/Runtime/ArrowWorker.queue',
		//最大读取长度
		'size'   => 128,
		//队列占用byte大小设置
		'length' => 10240000
    ],
	'test' => [
		//驱动类型
		'driver' => 'Queue',
		//路径
		'path'   => '/home/louis/github/ArrowWorker/App/Runtime/test.queue',
		//最大读取长度
		'size'   => 128,
		//队列占用byte大小设置
		'length' => 10240000
	]
];

//mongodb配置
$app['Mongo'] = [
    'app' => [
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
    ]
];

//rabbitMq配置
$app['Rabbitmq'] = [
    'app' => [
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
    ]
];

//web应用路由类型 1:get 2:uri
$app['RouterType'] = 1;

//渲染器
$app['View'] = [
    'app' => [
        //驱动类型
        'driver' => 'Smarty',
        //模板文件后缀
        'tplExt' => '.tpl'
    ]
];

//常驻服务配置
$app['Daemon'] = [
    'app' => [
        //驱动类型
        'driver' => 'ArrowDaemon',
        //进程名称
        'name'   => 'demo',
        //进程id文件名称
        'pid'    => 'ArrowWorker',
        //用户名
        'user'   => 'root',
        //线程数（在使用多线程模式下有效，依赖pthread扩展）
        'thread' => 4,
        //是否启用协成（不建议使用，调度损耗较大）
        'enableGenerator' => false,
        //日志文件路径（路路径必须存在，且对应文件夹要有相应权限）
        'log'    => '/var/log/ArrowWorker.log',
        //日志等级，1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
        'level'  => 30719
    ]
];

//swoole引擎
$app['swoole'] = [
    //端口
    'port'      => 9502,
    //工作进程数
    'workerNum' => 10,
    //是否常驻
    'daemonize' => false,
    //请求队列长度
    'backlog'   => 2000
];

//session相关配置
$app['Session'] = [
	//files；文件存储, RedisSession:redis存储, MemcacheSession:memcache存储
	'handler'  => 'files',
	//session文件储存路径,handler为files使用
	'savePath' => '/tmp',
	//redis/memcache地址，handler为RedisSession或MemcacheSession时使用
	'host'	   => '127.0.0.1',
	//redis/memcache端口，handler为RedisSession或MemcacheSession时使用
	'port'	   => 6379,
	//redis密码，handler为RedisSession时使用
	'password' => 'louis',
	//session超时时间
	'timeout'  => 3600,
];

return $app;
