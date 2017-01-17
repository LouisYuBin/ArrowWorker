<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 15:51
 */

namespace ArrowWorker;


class Load
{
    protected static $classMap = [];

    //加载Model
    static function load($class)
    {
        if(isset(self::$classMap[$class]))
        {
            return self::$classMap[$class];
        }
        else
        {
            self::$classMap[$class] = new $class;
            return self::$classMap[$class];
        }
    }
}