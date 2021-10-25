<?php

namespace App\Classes;

use ArrowWorker\Config;

class Method
{

    private static $config;

    public function __construct()
    {
        self::$config = Config::get();
    }

    public function godDamIt()
    {
        for ($i = 1; $i <= 100; $i++) {
            for ($j = 1; $j <= $i; $j++) {
            }
        }
        //echo PHP_EOL."App\Classes\Method";
    }

}

