<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 17-9-30
 * Time: 上午11:08
 */

namespace ArrowWorker;


class Exception
{

    public static function Init()
    {

    }

    public static function handle()
    {
        exit( json_encode( debug_backtrace() ) );
    }

}