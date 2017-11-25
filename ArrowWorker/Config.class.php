<?php
/**
 * User: Louis
 * Date: 2016/8/3 12:02
 * Update Records:
 *      2017-07-24 by Louis
 */

namespace ArrowWorker;

/**
 * Class Config
 * @package ArrowWorker
 */
class Config
{
    //app class map file
    public static $AppFileMap  = 'alias';

    //configuration file pathy
    private static $path        = '';
    private static $AppConfig   = [];
    private static $ExtraConfig = [];
    private static $configMap   = [];
    private static $appConfKey  = 'user';
    private static $configExt   = '.php';


	/**
	 * _init 初始化(配置文件路径)
	 */
	private static function _init()
    {
        if( self::$path == '' )
        {
            self::$path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_FOLDER . DIRECTORY_SEPARATOR;
        }
    }


	/**
	 * App
	 * @param string $key
	 * @param string $AppConfig
	 * @return array|mixed
	 */
	public static function App(string $key='', string $AppConfig=APP_CONFIG_FILE )
    {
        if( count( self::$AppConfig ) == 0 )
        {
            self::$AppConfig = self::Load( $AppConfig );
        }

        return ( $key != '' && isset(self::$AppConfig[$key]) ) ? self::$AppConfig[$key] : self::$AppConfig;
    }


	/**
	 * Extra 加载除默认配置文件以外的配置文件
	 * @param string $key
	 * @return mixed
	 */
	public static function Extra(string $key='' )
    {
        if( isset( self::$AppConfig[self::$appConfKey] ) && count( self::$AppConfig[self::$appConfKey] )>0 )
        {
            foreach( self::$AppConfig[self::$appConfKey] as $eachExtraConfig )
            {
                self::$ExtraConfig = array_merge( self::$ExtraConfig, self::Load( $eachExtraConfig ) );
            }
        }

        return ( $key != '' && isset(self::$appConfig[$key]) ) ? self::$appConfig[$key] : self::$appConfig;
    }

	/**
	 * Load 加载特定的配置文件
	 * @param string $fileName
	 * @return mixed
	 */
	public static function Load(string $fileName )
    {
        self::_Init();
        if( isset( self::$configMap[$fileName] ) )
        {
            return self::$configMap[$fileName];
        }
        else
        {
            self::$configMap[$fileName] = require( self::$path.$fileName.self::$configExt );
            return self::$configMap[$fileName];
        }

    }

}
