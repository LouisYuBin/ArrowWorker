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
    protected static $connPool = [];
    //单例模式对象
    protected static $instance;
    //数据库配置
    protected static $config = [];
    //当前选择的数据库连接
    protected static $dbCurrent = null;

    protected function __construct($config)
    {
        //Todo
    }

}