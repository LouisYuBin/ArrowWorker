<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:49
 */

namespace ArrowWorker\Driver;


class Cache
{
    protected static $cacheObj;
    //缓存配置
    protected static $config = [];
    //缓存连接对象
    protected static $CacheConn;

    protected function __construct($config)
    {
        self::$config = $config;
    }

}