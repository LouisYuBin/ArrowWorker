<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;

use ArrowWorker\Loader;
use ArrowWorker\Log;
use ArrowWorker\Chan;
use Swoole\Coroutine;


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

/*        $dbService    -> testDb();
        $cacheService -> testRedisLpush();
        $cacheService -> testRedisBrpop();
        $classService -> testMethod();*/

        $writeResult = Chan::Get()->Write("app".mt_rand(1,1000));
        Log::Info($writeResult);
        Coroutine::sleep(1);
    }

    public function channelApp()
    {

        $result  = Chan::Get()->Read();
        if( !$result )
        {
            return false;
        }
        Chan::Get('arrow')->Write($result);
        return true;
    }

    public function channelArrow()
    {

        $channel = Chan::Get('arrow');
        $result  = $channel->Read();
        //var_dump($result);
        if( !$result )
        {
            return false;
        }
		Chan::Get('test')->Write($result);
        return true;
    }

	public function channelTest()
	{
	    $result  = Chan::Get('test')->Read();
		if( !$result )
		{
			return false;
		}
		return true;
	}

}
