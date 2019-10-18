<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use App\Model\ArrowWorker;
use ArrowWorker\Cache;
use ArrowWorker\Coroutine;
use ArrowWorker\Db;
use ArrowWorker\Memory;
use ArrowWorker\Web\Cookie;
use ArrowWorker\Driver;
use ArrowWorker\Lib\Validation\ValidateImg;
use ArrowWorker\Loader;
use ArrowWorker\Log;
use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Web\Session;


class Index
{

    public function Index()
    {
        $rnd  = Request::Get("rnd");
        $this->db();
        $this->memory();
        $this->log();
        $this->cache();
        Coroutine::Sleep(2);
        Response::Json(200,['random'=>(int)$rnd],"ok");
    }

    public function cache()
    {
        $cache = Cache::Get();
        $cache->Set('arrow','louis');
        $cache->Get('arrow');
    }

    public function log()
    {
        Log::Error('M1过高M2过低，表明需求强劲、投资不足，存在通货膨胀风险；M2过高而M1过低，表明投资过热、需求不旺。','test2');
        Log::Warning('user not found in mysql db and redis cache,please checkout your user name[sdfsdfdsf]58745645654','test1');
        Log::Notice('If M1 grows faster, the consumer and terminal markets will be active; if M2 grows faster, investment and the middle market will be more active. The central bank and commercial banks can judge monetary policy accordingly.','test');
    }

    public function memory()
    {
        $key = 'memory_test_'.mt_rand(100,1000);
        Memory::Get('clients')->Write($key,[
            'id'        => 1,
            'token'     => 'token'.mt_rand(100,999),
            'name'      => 'name'.mt_rand(100,999),
            'loginTime' => date('Y-m-d H:i:s')
        ]);


        Memory::Get('clients')->ReadAll();
    }

    public function db()
    {
        ArrowWorker::GetOne();
        ArrowWorker::GetList();
    }

    function upload()
    {
        var_dump(Request::File('photo')->Save());
        Response::Json(200, Request::Files());
    }

    public function validation()
    {
        $cache = Cache::Get();
        $cache->Set("validate", mt_rand(1000,9999));

    }

	function session()
	{
       Response::Json(200,Request::Servers());
	}

    public function cookie()
    {
        Cookie::Set("louis","yubin");
        Cookie::Set("yubin","louis");
        var_dump(Cookie::Set("louis1111","0000============="));
        var_dump(Cookie::Get("louis1111"));
        Response::Write(Cookie::Get("louis"));
    }

    public function Request()
    {
        var_dump( Request::Servers() );
        Response::Write('router uri');
    }


}
