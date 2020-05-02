<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller\Demo;

use App\Model\ArrowWorker;
use ArrowWorker\Chan;
use ArrowWorker\Lib\Coroutine;
use ArrowWorker\Log;


class Demo
{

    public function Demo($argv = 0)
    {

        $writeResult = Chan::Get()->Write("app" . mt_rand(1, 1000));
        Log::Info($writeResult);
        Log::Debug('央行16日发布了一份重要报告，也就是2019年第三季度中国货币政策执行报告。报告提出，当前，中国经济增长保持韧性，但全球主要经济体货币政策空间有限，外部不确定不稳定因素增多。我国发展长短期、内外部等因素变化带来较多风险挑战，内生增长动力还有待进一步增强', [], 'News');


        ArrowWorker::GetOne();
        ArrowWorker::GetList();

        //Coroutine::Sleep(1);
        //WsPool::GetConnection()->Push(mt_rand(10000,99999));
        //Pool::GetConnection()->Send(mt_rand(10000,99999));
        return false;
    }

    public function channelApp()
    {

        $result = Chan::Get()->Read();
        if (!$result) {
            return false;
        }
        Log::Debug('Postion 1: Drama teacher (Primary school & Middle school) Start Date: February 2020 Requirements: ? Minimum Bachelor s degree in relevant field\'s Valid teaching certification or license in relevant subject from home country (required for IB positions) - preferred Previous teaching experience with ESL student\'s Minimum 2 years of relevant teaching experience Valid 120-hour TEFL or TESOL certificate for recent college graduate\'s Previous experience teaching the IB curriculum (preferred for IB positions Benefit: ? Salary 18000-23000 RMB/month before tax 17-month contract Accommodation allowance 3, 000 RMB/month? Airfare allowan ce 10600 RMB (17-month contract)? Medical insurance Work permit sponsorship Paid summer and winter vacation in addition to paid holidays in official Chinese Calendar? Professional Development Opportunity and paid training\'s Postion 2: Literature teacher (Primary school & Middle school) Start Date', [], 'English');
        ArrowWorker::GetOne();

        Chan::Get('arrow')->Write($result);
        return true;
    }

    public function channelArrow()
    {
        $channel = Chan::Get('arrow');
        $result = $channel->Read();
        if (!$result) {
            return false;
        }
        ArrowWorker::GetList();

        Chan::Get('test')->Write($result);
        return true;
    }

    public function channelTest()
    {
        $result = Chan::Get('test')->Read();
        if (!$result) {
            return false;
        }

        //ArrowWorker::GetOne();

        return true;
    }

}
