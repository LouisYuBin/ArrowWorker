<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;

use ArrowWorker\Controller as controller;
use ArrowWorker\Driver;
use ArrowWorker\Loader;


class Demo extends controller
{

    public function Demo($argv=0)
    {
        $cacheService = Loader::Service('CacheDemo');
        $dbService    = Loader::Service('DbDemo');
        $classService = Loader::Service('ClassDemo');

        $dbService    -> testDb();
        $cacheService -> testRedisLpush();
        $cacheService -> testRedisBrpop();
        $classService -> testMethod();
        $channel = Driver::Channel();
        $randamNum = mt_rand(0,10000000);
        sleep(1);
        $writeResult = $channel->Write("louis".$randamNum);
        //var_dump($writeResult);
    }

    public function pipe()
    {

        $channel = Driver::Channel();
        $readResult  = $channel->Read(false);
        //var_dump($readResult);
        //var_dump($readResult );
        sleep(1);
        //var_dump($readResult);
        if( empty($readResult) )
        {
            return 0;
        }
        return 1;
    }

}
