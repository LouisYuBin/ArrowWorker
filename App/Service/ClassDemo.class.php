<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;
use ArrowWorker\Loader;

class ClassDemo
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::App();
    }
    
    public function testMethod()
    {
        //$method = Loader::Classes("Method");
        //$method -> godDamIt();
        return "app -> service -> user -> add";
    }

}

