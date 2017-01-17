<?php
/**
 * User: Arrow
 * Date: 2016/8/1
 * Time: 19:48
 */

namespace ArrowWorker\Driver;


class Db
{
    //数据库连接池
    protected static $dbConnection = [];
    //单例模式对象
    protected static $dbObj;
    //数据库配置
    protected static $config = [];
    //数据库连接对象
    protected static $dbConn;

    protected function __construct($config)
    {
        self::$config = $config;
    }

}