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
    /**
     * app class map file
     * @var string
     */
    public static $AppFileMap  = 'alias';

    //configuration file pathy
    private static $path        = '';
    private static $AppConfig   = [];
    private static $ExtraConfig = [];
    private static $configMap   = [];
    private static $appConfKey  = 'user';
    private static $configExt   = '.php';

    //specify configuration file path
    private static function _Init()
    {
        if( empty(self::$path) )
        {
            self::$path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_FOLDER . DIRECTORY_SEPARATOR;
        }
    }

    //load frame work configuration

    /**
     * App
     * @author Louis
     * @param string $key
     * @param string $AppConfig
     * @return array|mixed
     */
    public static function App(string $key='',string $AppConfig=APP_CONFIG_FILE ) : mixed
    {
        if( count( self::$AppConfig ) == 0 )
        {
            self::$AppConfig = self::Load( $AppConfig );
        }

        return ( !empty($key) && isset(self::$AppConfig[$key]) ) ? self::$AppConfig[$key] : self::$AppConfig;
    }

    //load app configuration
    public static function Extra(string $key='' ) : mixed
    {
        //Load extra configuration
        if( isset( self::$AppConfig[self::$appConfKey] ) && count( self::$AppConfig[self::$appConfKey] )>0 )
        {
            foreach( self::$AppConfig[self::$appConfKey] as $eachExtraConfig )
            {
                self::$ExtraConfig = array_merge( self::$ExtraConfig, self::Load( $eachExtraConfig ) );
            }
        }

        return ( !empty($key) && isset(self::$appConfig[$key]) ) ? self::$appConfig[$key] : self::$appConfig;
    }

    /**
     * Load  load specified configuration file
     * @auth Louis
     * @param string $fileName
     * @return mixed
     * @throws \Exception
     */
    public static function Load(string $fileName ) : mixed
    {
        self::_Init();
        if( isset( self::$configMap[$fileName] ) )
        {
            return self::$configMap[$fileName];
        }

        $configPath = self::$path.$fileName.self::$configExt;
        if( !file_exists($configPath) )
        {
            throw new \Exception( "Config File : {$configPath} does not exists.");
        }
        self::$configMap[$fileName] = require( $configPath );
        return self::$configMap[$fileName];
    }

}
