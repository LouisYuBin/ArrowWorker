<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;

use ArrowWorker\Driver;
use ArrowWorker\Loader;
use ArrowWorker\Log;


class Demo
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

        $randamNum = 10000;

        $appChannel   = Driver::Channel();
        $writeResult = $appChannel->Write("app".$randamNum);
        Log::Info($writeResult);

    }

    public function channelApp()
    {

        $channel = Driver::Channel();
        $result  = $channel->Read();
        if( !$result )
        {
            return false;
        }
		Driver::Channel('arrow')->Write($result);
        return true;
    }

    public function channelArrow()
    {

        $channel = Driver::Channel('arrow');
        $result  = $channel->Read();
        //var_dump($result);
        if( !$result )
        {
            return false;
        }
		Driver::Channel('test')->Write($result);
        return true;
    }

	public function channelTest()
	{

		$channel = Driver::Channel('test');
		$result  = $channel->Read();
		if( !$result )
		{
			return false;
		}
		return true;
	}

}
