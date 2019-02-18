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
	const DEFAULT_CONTROLLER  = 'Index';

	const DEFAULT_METHOD = 'index';

	const CONTROLLER_NAMESPACE = '\\';

	private static $_restApiConfig = [];



	/**
	 * 路由返回格式
	 * @var array
	 */
	private static $func = ['c'=> self::DEFAULT_CONTROLLER, 'm' => self::DEFAULT_METHOD];

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

    }

    private static function exec()
    {
        $class  = self::$appController.ucfirst( static::$func['c'] );
        $method = ucfirst( static::$func['m'] );

        $controller = new $class;
        if( !method_exists($controller, static::$func['m']) )
        {
            throw new \Exception($class.'->'.$method.' does not exists',500);
        }

        $controller -> $method();
    }



}