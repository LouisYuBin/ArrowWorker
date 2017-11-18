<?php
/**
 * User: Louis
 * Date: 2016/8/2
 * Time: 10:35
 */

namespace App\Controller;

use ArrowWorker\Controller as controller;
use ArrowWorker\Loader;


class Demo extends controller
{

    public static function testWorker_1()
    {
        //加载用户类
        $class = Loader::Classes('Method');
        $class -> godDamIt();

        //加载service
        $user = Loader::Service('Project')->add();
    }

    public static function testWorker_2()
    {
        //加载用户类
        $class = Loader::Classes('Method');
        $class -> godDamIt();

        //加载service
        $user = Loader::Service('Project')->add();
    }

    public function dbDemo(){
        $user = Loader::Service('Project');
        $result = $user->testDb();
        //var_dump($result);
    }



}
