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
    public function __construct()
    {
        /**
         * @var $dbService \App\Service\DbDemo
         * @var $classService \App\Service\ClassDemo
         * @var $cacheService \App\Service\CacheDemo
         */
    }

    public function Demo($argv=0)
    {
        /**
         * @var $cacheService \App\Service\CacheDemo
         */
        $cacheService = Loader::Service('CacheDemo');

        /**
         * @var $dbService \App\Service\DbDemo
         */
        $dbService    = Loader::Service('DbDemo');

        /**
         * @var $classService \App\Service\ClassDemo
         */
        $classService = Loader::Service('ClassDemo');

        $dbService    -> testDb();
        $cacheService -> testRedisLpush();
        $cacheService -> testRedisBrpop();
        $classService -> testMethod();

        $randamNum = mt_rand(0,10000000);

        $appChannel   = Driver::Channel();
        $writeResult = $appChannel->Write("app".$randamNum);
        //var_dump($writeResult);

        $arrowChannel = Driver::Channel('arrow');
        $writeResult = $arrowChannel->Write("Arrow".$randamNum);
        //var_dump($writeResult);
        //sleep(1);
    }

    public function channelApp()
    {

        $channel = Driver::Channel();
        $result  = $channel->Read(false);
        //var_dump($result);
        if( !$result )
        {
            usleep(1000);
            return false;
        }
        return true;
    }

    public function channelArrow()
    {

        $channel = Driver::Channel('arrow');
        $result  = $channel->Read(false);
        //var_dump($result);
        if( !$result )
        {
            usleep(1000);
            return false;
        }
        return true;
    }

}
