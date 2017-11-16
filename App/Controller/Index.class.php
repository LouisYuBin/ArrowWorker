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

        $daemon =  Driver::Daemon('app');
        $workerCtl = new Demo();
        $daemon -> addTask(['function' => [$workerCtl,'dbDemo'], 'argv' => [100],'concurrency' => 4 , 'lifecycle' => 30, 'proName' => 'dbDemo_1']);
        $daemon -> addTask(['function' => [$workerCtl,'dbDemo'], 'argv' => [100],'concurrency' => 4 , 'lifecycle' => 30, 'proName' => 'dbDemo_2']);
        $daemon -> start();
    }


}
