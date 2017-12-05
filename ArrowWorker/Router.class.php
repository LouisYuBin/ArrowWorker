<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker;
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
	const defaults  = 'Index';

	/**
	 * 配置文件中路由配置key
	 */
	const routeType = "RouterType";

	/**
	 * get类型路由
	 */
    const get = 1;

	/**
	 * uri类型路由
	 */
    const uri = 2;


	/**
	 * 路由返回格式
	 * @var array
	 */
	private static $return = ['c'=> self::defaults, 'm' => self::defaults];


	/**
	 * getRouteType
	 * @return array|mixed
	 */
	private static function getRouteType()
    {
        return Config::App(static::routeType);
    }


	/**
	 * Get 返回要调用的控制器和方法
	 * @return array
	 */
	public static function Get()
    {
		$routerType = static::getRouteType();
        switch ($routerType){
            case static::get;
                // "get" 形式路由
                static::getRouter();
            break;
            case static::uri;
                // "?/类/方法" 形式路由
                static::uriRouter();
            break;
            default:
                //Todo
        }
        return self::$return;
    }

	/**
	 * getRouter get类型路由获取
	 */
	public static function getRouter()
    {
        @self::$return['c'] = isset($_REQUEST['c']) ? $_REQUEST['c'] : self::defaults;
        @self::$return['m'] = isset($_REQUEST['m']) ? $_REQUEST['m'] : self::defaults;
    }

	/**
	 * uriRouter uri类型路由获取
	 */
    public static function uriRouter()
    {
		//todo
    }
}