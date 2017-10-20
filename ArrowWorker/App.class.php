<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;
use ArrowWorker\Router as router;

class App
{
    //控制器
    private static $controller;
    //方法
    private static $method;
    //控制器和方法映射表
    private static $appClassMap;
    //app实例
    private static $appInstance;
    //应用命名空间
    private static $appControllerNamespace;

    //单例模式自启动构造函数
    private function __construct()
    {
        if(!self::$appClassMap)
        {
            self::$appClassMap  = require APP_PATH.DIRECTORY_SEPARATOR.APP_CONFIG_FOLDER.DIRECTORY_SEPARATOR.APP_ALIAS.'.php';
        }
        self::$appControllerNamespace = '\\'.APP_FOLDER.'\\'.APP_CONTROLLER_FOLDER.'\\';
    }

    //初始化app
    static function initApp()
    {
        if (!self::$appInstance)
        {
            self::$appInstance = new self;
        }
        return self::$appInstance;
    }

    //运行控制器
    public function runApp()
    {
        if(APP_TYPE=='cli')
        {
            $this->CliApp();
        }
        else
        {
            $this->WebApp();
        }
        $this -> isDefaultController();

        $controller = self::$appControllerNamespace.self::$controller;
        $method     = self::$method;
        $ctlObject  = new $controller;
        $ctlObject -> $method();
    }

    //web应用
    private function WebApp()
    {
        $router = router::Get();
        @self::$controller = $router['c'];
        @self::$method     = $router['m'];
    }

    //常驻服务
    private function CliApp()
    {
        if(php_sapi_name() != 'cli')
        {
            throw new \Exception("您当前模式为命令行模式，请在命令行执行相关命令，如：php index.php -c index -m index");
        }
        $inputs = getopt('c:m:');
        @self::$controller = isset($inputs['c']) ? $inputs['c'] : "Index";
        @self::$method     = isset($inputs['m']) ? $inputs['m'] : "Index";
    }

    //判断是否要应用默认控制器和方法
    private function isDefaultController()
    {
        self::$controller = is_null(self::$controller) ? DEFAULT_CONTROLLER : self::$controller;
        self::$method     = is_null(self::$method) ? DEFAULT_METHOD : self::$method;
    }

}
