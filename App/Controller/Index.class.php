<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use ArrowWorker\Controller as controller;
use ArrowWorker\Loader;


class Index extends controller
{
    function index()
    {
        //echo "ArrowWorker";

        $daemon =  Loader::Component('daemon');
        var_dump($daemon);
        $class = __CLASS__;
        $daemon -> addTask(['function' => [$class,'worker1'],'argv' => [100],'concurrency' => 4 ,'lifecycle' => 10,'proName' => 'Life_1_3_300']);
        $daemon -> addTask(['function' => [$class,'worker1'],'argv' => [100],'concurrency' => 1 , 'lifecycle' => 15 ,'proName' => 'Life_2_3_240']);
        $daemon -> start();

        echo "index_".mt_rand(1,199999).PHP_EOL;

    }

    function test()
    {
        echo "test".mt_rand(1,199999).PHP_EOL;
    }

    public static function worker1()
    {
        //加载用户类
        $class = Loader::Classes('Method');
        $class -> godDamIt();

        $user = Loader::Service('User')->add();
        //var_dump($user);
    }


}
