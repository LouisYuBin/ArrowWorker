<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller\Demo;

use App\Model\ArrowWorker;
use ArrowWorker\Component\Cache\Pool as Cache;
use ArrowWorker\Log;
use ArrowWorker\Memory;
use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Web\Session;


class Index
{

    public function Index()
    {
        $rnd = Request::Get("rnd");
        $this->db();
        $this->memory();
        $this->log();
        $this->cache();
        $this->session();
        $this->cookie();
        //Coroutine::Sleep(2);

        return ['random' => (int)$rnd];
    }

    public function session()
    {
        var_dump(Session::Set('loginTime', date('Y-m-d H:i:s')));
        var_dump(Session::Get('loginTime'));
        Session::MSet([
            'name' => 'louis',
            'age'  => 32,
            'home' => 'xunyi',
        ]);
        var_dump(Session::Info());
        var_dump(Session::Exists());
        var_dump(Session::Has('name'));
        var_dump(Session::Destroy());
        var_dump(Session::Create('b'));
    }

    public function cache()
    {
        $cache = Cache::Get();
        var_dump("======= Cache::Get() ======== ");
        if (false == $cache) {
            return;
        }

        var_dump("======= cache->Set('arrow','louis') ======== ");
        var_dump($cache->Set('arrow', 'louis'));
        var_dump("======= cache->Get('arrow','louis') ======== ");
        var_dump($cache->Get('arrow'));
    }

    public function log()
    {
        Log::Error('大道至简，实干为要。第二届中国国际进口博览会开幕式上，习近平主席在主旨演讲中，介绍了一年来中国各项开放举措的落实情况，正是要表明中国重诺守信、说话算数，中国用实际行动支持贸易自由化和经济全球化，用实际行动宣示“中国开放的大门只会越开越大”的决心。“比认识更重要的是决心，比方法更关键的是担当”，扩大开放，不做“清谈客”，要当“行动者”。中国不仅是“我家大门常打开，大道至简，实干为要。第二届中国国际进口博览会开幕式上，习近平主席在主旨演讲中，介绍了一年来中国各项开放举措的落实情况，正是要表明中国重诺守信、说话算数，中国用实际行动支持贸易自由化和经济全球化，用实际行动宣示“中国开放的大门只会越开越大”的决心。“比认识更重要的是决心，比方法更关键的是担当”，扩大开放，不做“清谈客”，要当“行动者”。中国不仅是“我家大门常打开，开放怀抱等你”，而且必将在与世界的交流融通中贡献更多中国智慧、中国方案、中国力量开放怀抱等你”，而且必将在与世界的交流融通中贡献更多中国智慧、中国方案、中国力量。', [], 'Solution');
        Log::Warning('vThe Fourth Plenary SessiXu Hongcai, deputy director of the Economic Policy Commission at China Association of Policy Science, said a significant portion of the Party meeting\'s communique was devoted to the idea of optimizing government functions and responsibilities in economic regulation, market supervision, social management and public service. That is the core of modernizing the country\'s governance and achieving the goal of deepening reform and ensuring stable and high-quality growth, he saidon of the 19th Communist Party of China Central Committee held last month sent a clear and strong message from the central leadership that building systems and institutions has been the key to the country\'s economic success, they said. Building such systems will continue to be the cornerstone and a critically important factor that matters for China\'s future development, given that the country\'s growth is seeing rising headwinds, they added.not found', [], 'English');
        Log::Notice('If The Party meeting highlighted several key tasks of the leadership, including improving the country\'s macroeconomic adjustment system with monetary and fiscal policies being two major pillars, optimizing medium- to long-term national strategic planning for economic and social development and building a modern central bank system and better legal protection for private and foreign businesses.M1.', [], 'News');
        Log::Info('此外，各党政机关单位应当按照《献血法》规定，积极动员和组织本单位干部职工参加无偿献血。军队有关单位应当积极组织广大官兵参加无偿献血；各级教育、卫生健康等行政部门和工会、共青团组织、妇联、红十字会等有关群团组织应当组织大中专院校、医疗卫生机构、国有企业及其他事业单位加强对职工和师生的宣传、教育、动员，鼓励职工和新京报快讯（记者 许雯）日前国家卫健委等十一部门联合发出关于进一步促进无偿献血工作健康发展的通知，提出探索将无偿献血纳入社会征信系统，并建立无偿献血激励机制，对献血者使用公共设通知提出，健全无偿献血激励机制。各地应当探索将无偿献血纳入社会征信系统，建立个人、单位、社会有效衔接的无偿献血激励机制，对献血者使用公共设通知还提出，各级卫生健康行政部门、军队有关卫生部门要加快推进血液管理信息互联互通，探索建立“互联网＋无偿献血”服务模式，为献血者提为强化血液应急保障能力，通知要求，完善区域间血液调配制度，会同交通运输等部门建立血液调配血液保障绿色通道，确保血液运输安全、有效、快捷。区域间血液调配不得收取互助金。供个性化服务。完善献血者及直系亲属出院时直接减免用血费用流程。施、参观游览政府办公园等提供优惠待遇，定期开展无偿献血表彰活动。施、参观游览政府据介绍，当前，全国一些地方还存在文物消防安全责任不落实、消防基础薄弱、管理粗放、火灾防控能力不强等问题，电气故障、生活用火、燃香烧纸、施工现场违规动火等文物火灾隐患依然存在，需要从制度机制入手，全面加强需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手和改进文物消防安全工作。办公园等提供优惠待遇。师生参加无偿献血', [], 'Article');
        Log::Info('此外，各党政机关单位应当按照《献血法》规定，积极动员和组织本单位干部职工参加无偿献血。军队有关单位应当积极组织广大官兵参加无偿献血；各级教育、卫生健康等行政部门和工会、共青团组织、妇联、红十字会等有关群团组织应当组织大中专院校、医疗卫生机构、国有企业及其他事业单位加强对职工和师生的宣传、教育、动员，鼓励职工和新京报快讯（记者 许雯）日前国家卫健委等十一部门联合发出关于进一步促进无偿献血工作健康发展的通知，提出探索将无偿献血纳入社会征信系统，并建立无偿献血激励机制，对献血者使用公共设通知提出，健全无偿献血激励机制。各地应当探索将无偿献血纳入社会征信系统，建立个人、单位、社会有效衔接的无偿献血激励机制，对献血者使用公共设通知还提出，各级卫生健康行政部门、军队有关卫生部门要加快推进血液管理信息互联互通，探索建立“互联网＋无偿献血”服务模式，为献血者提为强化血液应急保障能力，通知要求，完善区域间血液调配制度，会同交通运输等部门建立血液调配血液保障绿色通道，确保血液运输安全、有效、快捷。区域间血液调配不得收取互助金。供个性化服务。完善献血者及直系亲属出院时直接减免用血费用流程。施、参观游览政府办公园等提供优惠待遇，定期开展无偿献血表彰活动。施、参观游览政府据介绍，当前，全国一些地方还存在文物消防安全责任不落实、消防基础薄弱、管理粗放、火灾防控能力不强等问题，电气故障、生活用火、燃香烧纸、施工现场违规动火等文物火灾隐患依然存在，需要从制度机制入手，全面加强需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手需要从制度机制入手和改进文物消防安全工作。办公园等提供优惠待遇。师生参加无偿献血', [], 'Comment');
    }

    public function memory()
    {
        $key = 'memory_test_' . mt_rand(10, 99);
        Memory::Get('clients')->Write($key, [
            'id'        => 1,
            'token'     => 'token' . mt_rand(100, 999),
            'name'      => 'name' . mt_rand(100, 999),
            'loginTime' => "",
        ]);

        var_dump("======= Memory::Get('clients') ======== ");
        var_dump(Memory::Get('clients')->ReadAll());
    }

    public function db()
    {
        var_dump("======= ArrowWorker::GetOne ======== ");
        var_dump(ArrowWorker::GetOne());
        var_dump("======= ArrowWorker::GetList ======== ");
        var_dump(ArrowWorker::GetList());
    }

    function upload()
    {
        var_dump(Request::File('photo')->Save());
        return Request::Files();
    }

    public function validation()
    {
        $cache = Cache::Get();
        $cache->Set("validate", mt_rand(1000, 9999));
    }


    public function cookie()
    {
        Response::Cookie("louis", "yubin");
        Response::Cookie("yubin", "louis");
        var_dump(Response::Cookie("louis1111", "0000============="));
        var_dump('request cookie', Request::Cookie("louis1111"));
    }

    public function Request()
    {
        var_dump(Request::Servers());
        return 'router uri';
    }


}
