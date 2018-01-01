<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use ArrowWorker\Driver;
use ArrowWorker\Loader;
use ArrowWorker\Session;


class Index
{

	function index()
	{
		//phpinfo();
		//exit();
		var_dump( Session::Get('louis') );
		var_dump( Session::Set('louis','2018') );
		var_dump( Session::Get('louis') );
		var_dump( Session::Id() );

	}

    /*
    * （不建议使用）在常驻服务中调用service，然后直接常驻，这种方式当service、model、class等代码发生变更后需要重启服务，才能加载最新代码
    * */
    function daemonNoneUpdate()
    {
        $daemonDriver = Driver::Daemon('app');
        $cacheService = Loader::Service('CacheDemo');
        $dbService    = Loader::Service('DbDemo');
        $classService = Loader::Service('ClassDemo');
        $daemonDriver -> AddTask(['function' => [$cacheService,'testRedisLpush'], 'argv' => [100],'concurrency' => 5 , 'lifecycle' => 30, 'proName' => 'cacheService -> testRedisLpush']);
        $daemonDriver -> AddTask(['function' => [$cacheService,'testRedisBrpop'], 'argv' => [100],'concurrency' => 5 , 'lifecycle' => 30, 'proName' => 'cacheService -> testRedisBrpop']);
        $daemonDriver -> AddTask(['function' => [$dbService,   'testDb'],         'argv' => [100],'concurrency' => 50 , 'lifecycle' => 30, 'proName' => 'dbService -> testDb']);
        $daemonDriver -> AddTask(['function' => [$classService,'testMethod'],     'argv' => [100],'concurrency' => 5 , 'lifecycle' => 30, 'proName' => 'classService -> testMethod']);
        $daemonDriver -> Start();
    }

    /*
     * （建议使用）在常驻服务中调用controller，然后在controller中调用service，这种方式当service、model、class等代码发生变更后不需要重启服务，工作进程重启以后会自动加载最新代码
     * */
    function demonAutoUpdate()
    {
        $daemonDriver = Driver::Daemon('app');
        $demo = new Demo();
        $daemonDriver -> AddTask(['function' => [$demo, 'Demo'], 'argv' => [100], 'concurrency' => 4 , 'lifecycle' => 10, 'proName' => 'demo -> demo_1']);
        $daemonDriver -> AddTask(['function' => [$demo, 'Demo'], 'argv' => [90],  'concurrency' => 4 , 'lifecycle' => 10, 'proName' => 'demo -> demo_2']);

        $daemonDriver -> AddTask(['function' => [$demo, 'channelApp'],   'argv' => [80],  'concurrency' => 4 , 'lifecycle' => 10, 'proName' => 'demo -> channelApp',   'channel' => true]);
        $daemonDriver -> AddTask(['function' => [$demo, 'channelArrow'], 'argv' => [80],  'concurrency' => 4 , 'lifecycle' => 10, 'proName' => 'demo -> channelArrow', 'channel' => true]);
		$daemonDriver -> AddTask(['function' => [$demo, 'channeltest'], 'argv' => [80],  'concurrency' => 4 , 'lifecycle' => 10, 'proName' => 'demo -> channelArrow', 'channel' => true]);

        $daemonDriver -> Start();
    }


}
