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
	const defaultController  = 'Index';

	const defaultMethod = 'index';

	/**
	 * 配置文件中路由配置key
	 */
	const type = "RouterType";

	/**
	 * get类型路由
	 */
    const get = 1;

	/**
	 * uri类型路由
	 */
    const uri = 2;

    /**
     * @var string  应用命名空间
     */
    private static $appController = '\\'.APP_DIR.'\\'.APP_CONTROLLER_DIR.'\\';


	/**
	 * 路由返回格式
	 * @var array
	 */
	private static $func = ['c'=> self::defaultController, 'm' => self::defaultMethod];


	/**
	 * getRouterType
	 * @return int
	 */
	private static function getRouterType()
    {
        $type = Config::App(static::type);
        if( false===$type )
        {
            return static::get;
        }

        $type = (int)$type;
        if ( $type<static::get|| $type>static::uri )
        {
            return static::get;
        }
        return $type;
    }


	/**
	 * Exec 返回要调用的控制器和方法
	 * @return array
	 */
	public static function Start()
    {
        switch ( static::getRouterType() )
        {
            case static::get;
                static::getRouter();
                break;
            case static::uri;
                static::uriRouter();
                break;
            default:
                //Todo
                throw new \Exception("router type does not exists",500);
        }
        static::exec();
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

	/**
	 * getRouter get类型路由获取
	 */
	private static function getRouter()
    {
        $c = Request::Get('c');
        $m = Request::Get('m');
        @self::$func['c'] =  $c ? $c : self::defaultController;
        @self::$func['m'] =  $m ? $m : self::defaultMethod;
    }

	/**
	 * uriRouter uri类型路由获取
	 */
    private static function uriRouter()
    {
		//todo
    }
}