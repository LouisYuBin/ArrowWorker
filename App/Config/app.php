<?php
$app = [
    //应用其他配置文件
    'Extra' => ['user']
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
        //最大读取长度
        'msgSize'   => 128,
		//队列占用byte大小设置
		'bufSize' => 10240000
    ],
    'arrow' => [
        //驱动类型
        'driver' => 'Queue',
		//最大读取长度
		'msgSize'   => 128,
		//队列占用byte大小设置
		'bufSize' => 10240000
    ],
	'test' => [
		//驱动类型
		'driver' => 'Queue',
		//最大读取长度
		'msgSize'   => 128,
		//队列占用byte大小设置
		'bufSize' => 10240000
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

$app['Daemon'] = [
    'user' => 'louis',
    'pid'  => 'app1',
    'output'  => 'output',
    'appName' => 'ArrowWorker',
    'log'     => '/var/log/ArrowWorker.log',
    //日志等级，1:E_ERROR , 2:E_WARNING , 8:E_NOTICE , 2048:E_STRICT , 30719:all
    'errorLevel' => 30719,
];

$app['Log'] = [
    'type'    => 'File',
    'baseDir' => APP_PATH.DIRECTORY_SEPARATOR.APP_RUNTIME_DIR.'/Log',
    'bufSize' => 102400000,
    'fileSize' => 1073741824,
    'ip'       => '127.0.0.1',
    'port'     => 6379,
    'userName' => 'root',
    'password' => 'louis',
    'queue'    => 'ArrowWorkerLog'

];

//常驻服务配置
$app['Worker'] = [
    'app' => [
        //驱动类型
        'driver' => 'ArrowDaemon',

        'processor' => [
            [
                //function to be call
                'function'    => ['\\App\\Controller\\Demo','Demo'],
                'argv'        => [100],
                //number of process to be started
                'concurrency' => 3,
                //process lifecycle
                'lifecycle'   => 120,
                //process name
                'proName'     => 'Demo'
            ],
            [
                'function'    => ['\\App\\Controller\\Demo','channelApp'],
                'argv'        => [100],
                'concurrency' => 3,
                'lifecycle'   => 120,
                'proName'     => 'channelApp',
                'channel'     => true,
            ],
            [
                'function'    => ['\\App\\Controller\\Demo','channelArrow'],
                'argv'        => [100],
                'concurrency' => 3,
                'lifecycle'   => 120,
                'proName'     => 'channelArrow',
                'channel'     => true,
            ],
            [
                'function'    => ['\\App\\Controller\\Demo','channeltest'],
                'argv'        => [100],
                'concurrency' => 3,
                'lifecycle'   => 120,
                'proName'     => 'channeltest',
                'channel'     => true,
            ],
        ]
    ]
];

//swoole web引擎
$app['Swoole'] = [
    //web server
    'http' => [
        //port of listen
        'port'      => 9502,
        //number of worker process
        'workerNum' => 10,
        //size of request queue
        'backlog'   => 2000,
        //max post data length
        'maxContentLength' => 20889600
    ],
];

//session相关配置
$app['Session'] = [
	//RedisSession:redis存储, MemcachedSession:memcache存储
	'handler'  => 'RedisSession',
	//redis/memcache地址，handler为RedisSession或MemcacheSession时使用
	'host'	   => '127.0.0.1',
	//redis/memcache端口，handler为RedisSession或MemcacheSession时使用
	'port'	   => 6379,
	//用户名，对memcached有效
	'userName' => '',
	//redis密码，handler为RedisSession/memcached时使用
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

//文件上传相关配置
$app['Upload'] = [
    'savePath'  => APP_PATH.'/Runtime/Upload/',
    'extension' =>[
        'jpg',
        'jpeg',
        'zip',
        'rar',
        'png',
        'webp'
    ]
];

//验证码相关配置
$app['ValidationCode'] = [
    'codeLen' => 4,
    'with'    => 138,
    'height'  => 50,
    'font'    => [
        'en_ZEBRRA.ttf',
        'en_Kranky.ttf',
        'en_ARCADE.ttf'
    ],
    'fontSize' => 22,
];

//cookie相关配置
$app['Cookie'] = [
	//cookie前缀
	'prefix'  => 'ArrowWorker',
];


//加解密相关配置
$app['Cryto'] = [
    //加密/解密因子
    'factor'  => 'ArrowWorker',
];


return $app;
