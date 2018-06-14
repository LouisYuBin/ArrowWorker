<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use ArrowWorker\Cookie;
use ArrowWorker\Driver;
use ArrowWorker\Lib\Validation\ValidateImg;
use ArrowWorker\Loader;
use ArrowWorker\Log;
use ArrowWorker\Request;
use ArrowWorker\Response;
use ArrowWorker\Session;


class Index
{

    function Index()
    {
        $rnd  = Request::Get("rnd");
        //var_dump( Session::Id("session_".$rnd));
        //var_dump( Session::Id("u8888888") );
        //var_dump( Session::Get('louis') );
        //var_dump( Session::Set('louis','done') );
        //  var_dump( Session::Get('louis') );
        Session::Set('louis2','done');
        $louis2 = Session::Get('louis2');
        if( $louis2!==false )
        {
            Log::Info( $louis2 );
        }


        Session::MultiSet([
            'name' => 'louis',
            'do'   => 'good'
        ]);
        Log::Error('M1过高M2过低，表明需求强劲、投资不足，存在通货膨胀风险；M2过高而M1过低，表明投资过热、需求不旺。');
        Log::Warning('user not found in mysql db and redis cache,please checkout your user name[sdfsdfdsf]58745645654');
        //Log::Notice('If M1 grows faster, the consumer and terminal markets will be active; if M2 grows faster, investment and the middle market will be more active. The central bank and commercial banks can judge monetary policy accordingly.');
        //var_dump( Cookie::All() );
        //Session::Del('louis1');
        //var_dump( Session::Info() );

        Response::Json(200,['random'=>(int)$rnd],"ok");
    }

    function upload()
    {
        var_dump(Request::File('photo')->Save());
        Response::Json(200, Request::Files());
    }

    function validation()
    {
        $code = ValidateImg::Create();
        $cache = Driver::Cache();
        $cache->Set("validate", $code);

    }

	function session()
	{
       Response::Json(200,Request::Servers());
	}

    function cookie()
    {
        Cookie::Set("louis","yubin");
        Cookie::Set("yubin","louis");
        var_dump(Cookie::Set("louis1111","0000============="));
        var_dump(Cookie::Get("louis1111"));
        Response::Write(Cookie::Get("louis"));
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
