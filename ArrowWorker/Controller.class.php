<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/3
 * Time: 11:23
 */

namespace ArrowWorker;
use ArrowWorker\Factory as factory;

class Controller
{
    protected static $userConfig  = [];
    protected static $extraConfig = [];
    protected static $objectMap   = [];
    protected static $factory;

    public function __construct()
    {
        //加载应用配置文件
        self::$userConfig = config::get(APP_CONFIG_FILE);

        //加载用户其他配置文件
        if(isset(self::$userConfig['user']))
        {
            foreach(self::$userConfig['user'] as $config)
            {
                $extra = config::get($config);
                self::$extraConfig = array_merge(self::$extraConfig,$extra);
            }
        }
    }

    //加载类
    //updated at 2017/02/14 更新了加载类传入配置选择参数：1基本配置，2用户配置
    protected static function load( $class, $type='m', $property=1 )
    {
        $key = $type.'_'.$class;
        if(isset(self::$objectMap[$key]))
        {
            return self::$objectMap[$key];
        }
        else
        {
            $config = ($property==1) ? self::$userConfig : self::$extraConfig;
            $folder = ($type=='m') ? APP_Model_FOLDER : APP_Class_FOLDER;
            $class  = '\\'.APP_FOLDER.'\\'.$folder.'\\'.$class;
            //初始化并传入配置
            self::$objectMap[$key] = new $class( $config );
            //返回对象
            return self::$objectMap[$key];
        }
    }

    //将工厂类对应初始化并获取相应
    protected static function getObj($object)
    {
        if(!self::$factory)
        {
            self::$factory = factory::initFactory(self::$userConfig);
        }
        return self::$factory -> $object();
    }

}
