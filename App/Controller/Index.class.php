<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use App\Model\ArrowWorker;
use ArrowWorker\Db;
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
        Log::Error('M1过高M2过低，表明需求强劲、投资不足，存在通货膨胀风险；M2过高而M1过低，表明投资过热、需求不旺。','test2');
        Log::Warning('user not found in mysql db and redis cache,please checkout your user name[sdfsdfdsf]58745645654','test1');
        Log::Notice('If M1 grows faster, the consumer and terminal markets will be active; if M2 grows faster, investment and the middle market will be more active. The central bank and commercial banks can judge monetary policy accordingly.','test');
        var_dump(Db::Get());
        for( $i=0; $i<2; $i++)
        {
             var_dump(ArrowWorker::GetOne());
        }
        var_dump(ArrowWorker::GetList());
        Response::Json(200,['random'=>(int)$rnd],"ok");
    }

    function upload()
    {
        var_dump(Request::File('photo')->Save());
        Response::Json(200, Request::Files());
    }

    public function validation()
    {
        $code = ValidateImg::Create();
        $cache = Driver::Cache();
        $cache->Set("validate", $code);

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
