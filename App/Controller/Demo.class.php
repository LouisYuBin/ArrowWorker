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

    public function Demo()
    {
        $cacheService = Loader::Service('CacheDemo');
        $dbService    = Loader::Service('DbDemo');
        $classService = Loader::Service('ClassDemo');

        $dbService    -> testDb();
        $cacheService -> testRedisLpush();
        $cacheService -> testRedisBrpop();
        $classService -> testMethod();
    }

    public function pipe()
    {

        $channel = Driver::Channel();
        $writeResult = $channel->Write("louis");
        var_dump($writeResult);
        $readResult  = $channel->Read(false);
        var_dump($readResult );

        $writeResult = $channel->Write("加油");
        var_dump($writeResult);
        $readResult  = $channel->Read(false);
        var_dump($readResult );
    }

}
