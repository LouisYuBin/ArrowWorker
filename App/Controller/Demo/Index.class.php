<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller\Demo;

use App\Model\ArrowWorker;
use ArrowWorker\Cache;
use ArrowWorker\Lib\Coroutine;
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
        //Coroutine::Sleep(2);
        Response::Json(200,['random'=>(int)$rnd],"ok");
    }

    public function cache()
    {
        $cache = Cache::Get();
        if( false==$cache )
        {
            return ;
        }
        $cache->Set('arrow','louis');
        $cache->Get('arrow');
    }

    public function log()
    {
        Log::Error('大道至简，实干为要。第二届中国国际进口博览会开幕式上，习近平主席在主旨演讲中，介绍了一年来中国各项开放举措的落实情况，正是要表明中国重诺守信、说话算数，中国用实际行动支持贸易自由化和经济全球化，用实际行动宣示“中国开放的大门只会越开越大”的决心。“比认识更重要的是决心，比方法更关键的是担当”，扩大开放，不做“清谈客”，要当“行动者”。中国不仅是“我家大门常打开，大道至简，实干为要。第二届中国国际进口博览会开幕式上，习近平主席在主旨演讲中，介绍了一年来中国各项开放举措的落实情况，正是要表明中国重诺守信、说话算数，中国用实际行动支持贸易自由化和经济全球化，用实际行动宣示“中国开放的大门只会越开越大”的决心。“比认识更重要的是决心，比方法更关键的是担当”，扩大开放，不做“清谈客”，要当“行动者”。中国不仅是“我家大门常打开，开放怀抱等你”，而且必将在与世界的交流融通中贡献更多中国智慧、中国方案、中国力量开放怀抱等你”，而且必将在与世界的交流融通中贡献更多中国智慧、中国方案、中国力量。','test2');
        Log::Warning('vThe Fourth Plenary SessiXu Hongcai, deputy director of the Economic Policy Commission at China Association of Policy Science, said a significant portion of the Party meeting\'s communique was devoted to the idea of optimizing government functions and responsibilities in economic regulation, market supervision, social management and public service. That is the core of modernizing the country\'s governance and achieving the goal of deepening reform and ensuring stable and high-quality growth, he saidon of the 19th Communist Party of China Central Committee held last month sent a clear and strong message from the central leadership that building systems and institutions has been the key to the country\'s economic success, they said. Building such systems will continue to be the cornerstone and a critically important factor that matters for China\'s future development, given that the country\'s growth is seeing rising headwinds, they added.not found','test1');
        Log::Notice('If The Party meeting highlighted several key tasks of the leadership, including improving the country\'s macroeconomic adjustment system with monetary and fiscal policies being two major pillars, optimizing medium- to long-term national strategic planning for economic and social development and building a modern central bank system and better legal protection for private and foreign businesses.M1.','test');
    }

    public function memory()
    {
        $key = 'memory_test_'.mt_rand(10,99);
        Memory::Get('clients')->Write($key,[
            'id'        => 1,
            'token'     => 'token'.mt_rand(100,999),
            'name'      => 'name'.mt_rand(100,999),
            'loginTime' => ""
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
