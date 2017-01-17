<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 11:19
 */

namespace ArrowWorker;
use ArrowWorker\Factory as factory;

class Model
{
    public $db;
    public $cache;
    public $daemon;
    protected static $factory;
    protected static $config;
    protected static $modelObj;
    protected static $userConfig;

    public function __construct($config)
    {
        self::$config = $config;
        if(!self::$factory)
        {
            self::$factory = factory::initFactory(self::$config);
        }

        $this ->getDb();
    }

    public function getDb()
    {
        $this ->db = self::$factory->db();
    }

    public function getCache()
    {
        $this ->cache = self::$factory->cache();
    }

    public function getDaemon()
    {
        $this ->daemon = self::$factory->daemon();
    }

    //将用户配置加载进model中
    public function getConfig($config)
    {
        if(!self::$userConfig)
        {
            self::$userConfig = $config;
        }
    }

}