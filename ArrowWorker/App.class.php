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
     * @var App实例
     */
    private static $appInstance;

    /**
     * App constructor. 单例模式自启动构造函数
     */
    private function __construct()
    {
        //todo
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
            Console::StartProcessor();
        }
        else if(APP_TYPE == 'swWeb')
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
        Router::Start();
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
            Cookie::Init(is_array($request->cookie) ? $request->cookie : [], $response);
            Request::Init(
                is_array($request->get)   ? $request->get : [],
                is_array($request->post) ? $request->post : [],
                is_array($request->server) ? $request->server : [],
                is_array($request->files) ? $request->files : []
            );
            Response::Init($response);
            Router::Start();
        });

        $server->start();
    }


}
