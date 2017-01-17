<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 10:00
 */

namespace ArrowWorker;


class Factory
{
    private static $config = [];
    private static $factoryObj;


    private function __construct($config)
    {
        self::$config = $config;
    }

    public static function initFactory($config)
    {
        if(!self::$factoryObj)
        {
            self::$factoryObj = new self($config);
        }
        return self::$factoryObj;
    }

    public function db()
    {
        $class= self::$config['db']['driver'];
        $class = 'ArrowWorker\\Driver\\Db\\'.$class;
        return $class::initDb(self::$config['db']);
    }

    public function cache()
    {
        $class = self::$config['cache']['driver'];
        $class = 'ArrowWorker\\Driver\\Cache\\'.$class;
        return $class::initCache(self::$config['cache']);
    }

    public function daemon()
    {
        $class = self::$config['daemon']['driver'];
        $class = 'ArrowWorker\\Driver\\Daemon\\'.$class;
        return $class::initDaemon(self::$config['daemon']);
    }

    public function view()
    {
        $class = 'ArrowWorker\\Driver\\View';
        return $class::initView(self::$config['view']);
    }
}