<?php
/**
 * User: Louis
 * Date: 2016/8/4
 * Time: 9:18
 */

namespace ArrowWorker\Component;


class View
{
    //单例模式对象
    protected static $ViewObj;
    //数据库配置
    protected static $config = [];

    protected static $tplObj;

    protected function __construct($config)
    {
        self::$config = $config;
    }

    //初始化smarty
    public static function init($config)
    {

        if(!self::$ViewObj)
        {
            self::$ViewObj = new self($config);
        }
        return self::$ViewObj;
    }

    //初始化引擎
    private function initEngine()
    {
        if(!self::$tplObj)
        {
            $driver       = "ArrowWorker\\Driver\\View\\".self::$config['driver'];
            self::$tplObj = new $driver;
            $TplPath      = APP_PATH.APP_TPL_DIR.DIRECTORY_SEPARATOR;

            self::$tplObj -> template_dir = $TplPath;
            self::$tplObj -> compile_dir  = $TplPath.'Compile';
            self::$tplObj -> html_dir     = $TplPath.'Html';
            self::$tplObj -> cache_dir    = $TplPath.'Cache';
        }
    }

    //传递参数
    public function assign($key,$value)
    {
        $this -> initEngine();
        self::$tplObj -> assign($key,$value);
    }

    //映射fetch方法
    public function fetch($tplName)
    {
        $this -> initEngine();
        return self::$tplObj -> fetch($tplName.self::$config['tplExt']);
    }

}