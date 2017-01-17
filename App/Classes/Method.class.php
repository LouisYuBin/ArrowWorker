<?php

namespace App\Classes;

class Method
{
    
    private static $config;

    public function __construct($config)
    {
        self::$config = $config;   
    }
    
    public function godDamIt()
    {
        for($i=1; $i<=100; $i++)
        {
            for($j=1; $j<=$i; $j++)
            {
                //echo $i."*".$j,'='.($i*$j).' ';
                $k = $i*$j;
            }
            //echo PHP_EOL;
        }
    }

}

