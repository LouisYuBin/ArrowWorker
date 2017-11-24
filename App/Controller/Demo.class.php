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

    public function dbDemo()
    {
        $project = Loader::Service('Project');
        $result = $project->testDb();
        //var_dump($result);
        //sleep(1);
    }

    public function cacheSetDemo()
    {
        $project = Loader::Service('Project');
        $return = $project ->testRedisGet();
        //var_dump($return);
    }

    public function cacheGetDemo()
    {
        $project = Loader::Service('Project');
        $return = $project ->testRedisGet();
        //var_dump($return);
    }

    public function cacheLpushDemo()
    {
        $cache = Loader::Service('CacheDemo');
        $cache -> testRedisLpush();
        $cache -> testRedisBrpop();
    }


}
