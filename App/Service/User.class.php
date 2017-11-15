<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Loader;

class User
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::App();
    }
    
    public function add()
    {
        $method = Loader::Classes("Method");
        $method -> godDamIt();
        return "app -> service -> user -> add";
    }

    public function test()
    {
        $test = Loader::Model('Test');
        return $test->test();
    }

}

