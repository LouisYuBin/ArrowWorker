<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker\Web;

use ArrowWorker\Config;


/**
 * Class Router
 * @package ArrowWorker
 */
class Router
{
	/**
	 * 默认控制器/方法
	 */
	const DEFAULT_CONTROLLER  = 'Index';

	const DEFAULT_METHOD = 'index';

	const CONTROLLER_NAMESPACE = '\\'.APP_DIR.'\\'.APP_CONTROLLER_DIR.'\\';

	private static $_restApiConfig = [];

	public static function Init()
    {
        self::_loadRestConfig();
    }

    private static function _loadRestConfig()
    {
        $config = Config::Get('Rest');
        if( false===$config )
        {
            Log::Warning("Load rest api configuration failed");
        }
        static::$_restApiConfig = $config;
    }


	/**
	 * Go 返回要调用的控制器和方法
	 */
	public static function Go()
    {
        if( static::_restRouter() )
        {
            return ;
        }

        if( static::_pathInfoRouter() )
        {
            return ;
        }
        if( static::_routeToDefault() )
        {
            return ;
        }
        static::_logAndResponse("request does not match any router");
    }

    private static function _restRouter()
    {
        $uri    = Request::Server('REQUEST_URI');
        $method = strtolower(Request::Method());
        if( !isset(static::$_restApiConfig[$uri]) )
        {
            return false;
        }

        if( !isset(static::$_restApiConfig[$uri][$method]) )
        {
            return false;
        }

        list($class, $function) = explode('::',static::$_restApiConfig[$uri][$method]);
        $class = self::CONTROLLER_NAMESPACE.$class;
        return static::_routeToFunction($class, $function);
    }

    private static function _pathInfoRouter()
    {
        $uri      = Request::Server('REQUEST_URI');
        $pathInfo = explode('/', $uri);
        $pathLen  = count($pathInfo);
        if( $pathLen>=3 )
        {
            $class = self::CONTROLLER_NAMESPACE.$pathInfo[0].'\\'.$pathInfo[1];
            return static::_routeToFunction($class, $pathInfo[2]);
        }
        else if( $pathLen==2 )
        {
            $class = self::CONTROLLER_NAMESPACE.$pathInfo[0];
            return static::_routeToFunction($class, $pathInfo[1]);
        }
        return false;
    }

    private static function _routeToDefault()
    {
        $class = self::CONTROLLER_NAMESPACE.DEFAULT_CONTROLLER;
        return static::_routeToFunction($class, DEFAULT_METHOD);
    }

    private static function _routeToFunction(string $class, string $function)
    {
        if( !class_exists($class) )
        {
            static::_logAndResponse("rest api : {$class} does not exists.");
        }

        $controller = new $class;
        if( !method_exists($controller, $function) )
        {
            static::_logAndResponse("rest api : {$class}->{$function} does not exists.");
        }
        $controller->$function();
        return true;
    }

    private static function _logAndResponse(string $msg)
    {
        Log::Warning($msg);
        Response::Write($msg);
    }


}