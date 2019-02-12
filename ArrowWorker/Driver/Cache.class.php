<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:49
 */

namespace ArrowWorker\Driver;


class Cache
{
    protected static $instance;
    //缓存配置
    protected static $config = [];
    //缓存连接对象
    protected static $cacheConn = [];
    //缓存连接池
    protected static $connPool = [];
    //缓存连接对象
    protected static $current;

    protected function __construct($config)
    {
        //todo
    }

}