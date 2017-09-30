<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;
use ArrowWorker\Controller as controller;


class Index extends controller
{
    function index()
    {
        echo "ArrowWorker";
        $C = $A+"SDF";
      /*  $class = self::load('Method','c');

        $daemon =  self::getObj('daemon');
        $class = __CLASS__;
        $daemon -> addTask(['function' => [$class,'worker1'],'argv' => [100],'concurrency' => 4 ,'lifecycle' => 10,'proName' => 'Life_1_3_300']);
        //$daemon -> addTask(['function' => [$class,'worker2'],'argv' => [100],'concurrency' => 1 , 'lifecycle' => 15 ,'proName' => 'Life_2_3_240']);
        $daemon -> start();*/

    }

    public static function worker1($arg)
    {
        $class = self::load('Method','c');
        $class -> godDamIt();

    }

    public static function worker2($arg)
    {
        $class = self::load('Method','c');
        $class -> godDamIt();
    }



}
