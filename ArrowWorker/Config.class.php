<?php
/**
 * User: Louis
 * Date: 2016/8/3 12:02
 * Update Records:
 *      2017-07-24 by Louis
 */

namespace ArrowWorker;


class Config
{
    //app class map file
    public static $AppFileMap  = 'alias';

    //configuration file pathy
    private static $path        = null;
    private static $AppConfig   = [];
    private static $ExtraConfig = [];
    private static $configMap   = [];
    private static $appConfKey  = 'user';
    private static $configExt   = '.php';

    //specify configuration file path
    private static function _Init()
    {
        if( is_null(self::$path) )
        {
            self::$path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_FOLDER . DIRECTORY_SEPARATOR;
        }
    }

    //load frame work configuration
    public static function App( $key=null, $AppConfig=APP_CONFIG_FILE )
    {
        if( count( self::$AppConfig ) == 0 )
        {
            self::$AppConfig = self::Load( $AppConfig );
        }

        return ( !is_null($key) && isset(self::$AppConfig[$key]) ) ? self::$AppConfig[$key] : self::$AppConfig;
    }

    //load app configuration
    public static function Extra( $key=null )
    {
        //Load extra configuration
        if( isset( self::$AppConfig[self::$appConfKey] ) && count( self::$AppConfig[self::$appConfKey] )>0 )
        {
            foreach( self::$AppConfig[self::$appConfKey] as $eachExtraConfig )
            {
                self::$ExtraConfig = array_merge( self::$ExtraConfig, self::Load( $eachExtraConfig ) );
            }
        }

        return ( !is_null($key) && isset(self::$appConfig[$key]) ) ? self::$appConfig[$key] : self::$appConfig;
    }

    //load specified configuration
    public static function Load( $fileName )
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
