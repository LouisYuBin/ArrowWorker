<?php

namespace App\Classes;

use ArrowWorker\Config;

class Method
{
    
    private static $config;

    public function __construct()
    {
        $config = Config::Arrow();
        var_dump($config);
    }
    
    public function godDamIt()
    {
        for($i=1; $i<=100; $i++)
        {
            for($j=1; $j<=$i; $j++)
            {
            }
        }
        echo "godDamIt";
    }

}

