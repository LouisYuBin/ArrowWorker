<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;
use ArrowWorker\Loader;

class CacheDemo
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::App();
    }

    public function testRedisSet()
    {
        return Driver::Cache() -> Set("louis","good");
    }

    public function testRedisGet()
    {
        return  Driver::Cache() -> Get('louis');
    }

    public function testRedisLpush()
    {
        $result = Driver::Cache() -> Lpush('ArrowWorker','An efficient php deamon Framework.');
        //var_dump($result);
    }

    public function testRedisBrpop()
    {
        $result = Driver::Cache() -> Brpop('ArrowWorker',3);
        //var_dump($result);
    }

}

