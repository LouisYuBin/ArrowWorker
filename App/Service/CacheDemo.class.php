<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;
use ArrowWorker\Loader;
use ArrowWorker\Log;

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
        $result = Driver::Cache() -> Lpush('ArrowWorker',100);
        Log::Info( $result );
    }

    public function testRedisBrpop()
    {
        $result = Driver::Cache() -> Brpop(3,'ArrowWorker');
        Log::Info( json_encode($result) );
    }

}

