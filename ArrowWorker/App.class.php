<?php
/**
 * User: Louis
 * Date: 2016/8/1 19:47
 */

namespace ArrowWorker;
use ArrowWorker\Router;

/**
 * 应用加载/启动类
 * Class App
 * @package ArrowWorker
 */
class App
{

    /**
     * @var mixed  控制器和方法映射表
     */
    private static $appClassMap;

    /**
     * @var App实例
     */
    private static $appInstance;

    /**
     * @var string  应用命名空间
     */
    private static $appControllerNamespace;


    /**
     * App constructor. 单例模式自启动构造函数
     */
    private function __construct()
    {
        if(!self::$appClassMap)
        {
            self::$appClassMap  = require APP_PATH.DIRECTORY_SEPARATOR.APP_CONFIG_FOLDER.DIRECTORY_SEPARATOR.APP_ALIAS.'.php';
        }
        self::$appControllerNamespace = '\\'.APP_FOLDER.'\\'.APP_CONTROLLER_FOLDER.'\\';
    }

    //初始化app

    /**
     * initApp 单例模式初始化app类
     * @author Louis
     * @return App
     */
    static function InitApp()
    {
        if (!self::$appInstance)
        {
            self::$appInstance = new self;
        }
        return self::$appInstance;
    }


    /**
     * RunApp 执行应用
     * @author Louis
     */
    public function RunApp()
    {
        if(APP_TYPE=='cli')
        {
            $this -> _cliApp();
        }
        else if(APP_TYPE == 'swooleWeb')
        {
            $this -> _swooleWeb();
        } else {
            $this -> _webApp();
        }
    }

    /**
     * _webApp web应用（nginx+fpm）
     * @author Louis
     */
    private function _webApp()
    {
        //读取路由
        $router = Router::Get();
        $controller = self::$appControllerNamespace.ucfirst($router['c']);
        $method     = ucfirst($router['m']);
        $ctlObject  = new $controller;
        $ctlObject -> $method();
        if( !method_exists($ctlObject, $method) )
        {
            throw new \Exception("controller : function:".$controller."->".$method."does not exists",500);
        }
    }

    /**
     * _swooleWebApp web应用（swoole web）
     * @author Louis
     */
    private function _swooleWeb()
    {
        $config = Config::App("swoole");
        $server = new \swoole_http_server("0.0.0.0", $config['port']);
        $server->set([
            'worker_num' => $config['workerNum'],
            'daemonize'  => $config['daemonize'],
            'backlog'    => $config['backlog'],
        ]);
        $server->on('Request', function($request, $response) {
            Cookie::Init($request->cookie ?? [], $response);
            Request::Init(
                $request->get ?? [],
                $request->post ?? [],
                $request->server ?? [],
                $request->files ?? []
            );
            Response::Init($response);

            $router = Router::Get();
            $class  = self::$appControllerNamespace.ucfirst($router['c']);
            $method = ucfirst($router['m']);
            $controller = new $class;
            $controller -> $method();
        });

        $server->start();
    }

    /**
     * _cliApp 常驻服务应用
     * @author Louis
     */
    private function _cliApp()
    {
        if(php_sapi_name() != 'cli')
        {
            throw new \Exception("您当前模式为命令行模式，请在命令行执行相关命令，如：php index.php -c index -m index");
        }
        $inputs = getopt('c:m:');
        $controller = isset($inputs['c']) ? ucfirst($inputs['c']) : "Index";
        $method     = isset($inputs['m']) ? ucfirst($inputs['m']) : "Index";
        $controller = self::$appControllerNamespace.$controller;
        $ctlObject  = new $controller;
        $ctlObject -> $method();
    }

}
