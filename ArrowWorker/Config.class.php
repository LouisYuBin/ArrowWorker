<?php
/**
 * User: Administrator
 * Date: 2016/8/3
 * Time: 12:02
 */

namespace ArrowWorker;


class Config
{

    private static $configObj;
    private static $Path;
    private static $lang = ['zh-cn','en-us'];
    private static $configMap = [];
    private static $configExt = '.php';

    private function __construct()
    {
        self::$Path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_FOLDER . DIRECTORY_SEPARATOR;
    }

    static function get($fileName,$folder=0,$langIndex=0)
    {
        if(!self::$configObj)
        {
            self::$configObj = new self;
        }
        if($folder==1)
        {

            self::$Path = APP_PATH . DIRECTORY_SEPARATOR . APP_LANG_FOLDER . DIRECTORY_SEPARATOR . self::$lang[$langIndex] . DIRECTORY_SEPARATOR;
        }

        return self::$configObj->load($fileName);
    }

    private function load($fileName)
    {
        if(isset(self::$configMap[$fileName]))
        {
            return self::$configMap[$fileName];
        }
        else
        {
            $config = require(self::$Path.$fileName.self::$configExt);
            self::$configMap[$fileName] = $config;
            return $config;
        }

    }

}