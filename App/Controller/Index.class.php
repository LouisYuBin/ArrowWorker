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


class Index extends controller
{
    function index()
    {

        Loader::Classes('Method');
        Loader::Service('User');
        Driver::View();
        $daemon =  Driver::Daemon('app');
        $workerCtl = new Demo();
        $daemon -> addTask(['function' => [$workerCtl,'testWorker_1'], 'argv' => [100],'concurrency' => 4 , 'lifecycle' => 100, 'proName' => 'Life_1_3_300']);
        $daemon -> addTask(['function' => [$workerCtl,'testWorker_2'], 'argv' => [100],'concurrency' => 1 , 'lifecycle' => 100, 'proName' => 'Life_2_3_240']);
        $daemon -> start();
    }

    function db()
    {
        $user = Loader::Service('User');
        $result = $user->test();
        var_dump($result);
    }

}
