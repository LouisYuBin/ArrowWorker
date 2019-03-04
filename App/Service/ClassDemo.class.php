<?php

namespace App\Service;

use ArrowWorker\Config;
use ArrowWorker\Driver;

class ClassDemo
{
    
    private static $config;

    public function __construct()
    {
        self::$config = Config::Get();
    }
    
    public function testMethod()
    {
        //$method = Loader::Classes("Method");
        //$method -> godDamIt();
        return "app -> service -> user -> add";
    }

}

