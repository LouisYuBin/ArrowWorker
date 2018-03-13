<?php
/**
 * User: Louis
 * Date: 2016/8/3 15:51
 * Update Record:
 *      2017-07-24 By Louis
 */

namespace ArrowWorker;

/**
 * 用户类加载器
 * Class Loader
 * @package ArrowWorker
 */
class Loader
{
    /**
     * 用户定义类加载map
     * @var array
     */
    private static $appClass    = [];


    /**
     * Model 加载用户model
     * @author Louis
     * @param string $name
     * @return mixed
     */
    public static function Model( string $name )
    {
        return self::_appModule( $name, APP_MODEL_FOLDER );
    }


    /**
     * Classes 加载用户定义类
     * @author Louis
     * @param string $name
     * @return mixed
     */
    public static function Classes( string $name )
    {
        return self::_appModule( $name, APP_CLASS_FOLDER );
    }

	/**
     * Service 加载用户 service
	 * @param string $name
	 * @return \App\Service\DbService
	 */
    public static function Service( string $name )
    {
        return self::_appModule( $name, APP_SERVICE_FOLDER );
    }

    /**
     * _appModule 加载用户模块
     * @param $name
     * @return \App\Service\DbService
     */
    private static function _appModule(string $name, string $type=APP_MODEL_FOLDER )
    {
        $key = $type.$name;
        if( !isset( self::$appClass[$key] ) )
        {
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.ucfirst( $name );
            self::$appClass[$key] = new $class;
        }
        return self::$appClass[$key];
    }

}
