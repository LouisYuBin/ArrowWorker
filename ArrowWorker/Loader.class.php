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
     * @return \App\Model\*
     */
    public static function Model( string $name )
    {
        return self::_appModule( $name, APP_MODEL_DIR );
    }


    /**
     * Classes 加载用户定义类
     * @author Louis
     * @param string $name
     * @return \App\Classes\*
     */
    public static function Classes( string $name )
    {
        return self::_appModule( $name, APP_CLASS_DIR );
    }

	/**
     * Service 加载用户 service
	 * @param string $name
	 * @return \App\Service\*
	 */
    public static function Service( string $name )
    {
        return self::_appModule( $name, APP_SERVICE_DIR );
    }

    /**
     * _appModule 加载用户模块
     * @param $name
     * @return \App\Model\*|\App\Service\*|\App\Classes\*
     */
    private static function _appModule(string $name, string $type=APP_MODEL_DIR )
    {
        $key = $type.$name;
        if( !isset( self::$appClass[$key] ) )
        {
            $class  = '\\'.APP_DIR.'\\'.$type.'\\'.ucfirst( $name );
            self::$appClass[$key] = new $class;
        }
        return self::$appClass[$key];
    }

}
