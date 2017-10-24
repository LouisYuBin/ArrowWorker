<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;
use ArrowWorker\Router as router;

class App
{
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
    }

    //web应用
    private function WebApp()
    {
        $serv = new \Swoole\Http\Server("127.0.0.1", 9502);
        $serv->on('Request', function($request, $response) {
            $_GET    = $request->get;
            $_POST   = $request->post;
            $_COOKIE = $request->cookie;
            $_REQUEST = [];
            if(is_array($_GET) && is_array($_POST))
            {
                $_REQUEST = array_merge($_GET,$_REQUEST);
            }
            else if(!is_array($_GET) && is_array($_POST))
            {
                $_REQUEST = $_POST;
            }
            else if (is_array($_GET) && !is_array($_POST))
            {
                $_REQUEST = $_GET;
            }
            $_FILES = $request->files;
            $_SERVER = $request->server;
            $router = router::Get();
            $controller = self::$appControllerNamespace.$router['c'];
            $method     = self::$router['m'];
            $ctlObject  = new $controller;
            $ctlObject -> $method($response);

        });

        $serv->start();
    }

    //常驻服务
    private function CliApp()
    {
        if(php_sapi_name() != 'cli')
        {
            throw new \Exception("您当前模式为命令行模式，请在命令行执行相关命令，如：php index.php -c index -m index");
        }
        $inputs = getopt('c:m:');
        $controller = isset($inputs['c']) ? $inputs['c'] : "Index";
        $method     = isset($inputs['m']) ? $inputs['m'] : "Index";
        $controller = self::$appControllerNamespace.$router['c'];
        $method     = self::$router['m'];
        $ctlObject  = new $controller;
        $ctlObject -> $method($response);
    }

}
