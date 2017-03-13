<?php
/**
 * User: Administrator
 * Date: 2016/8/3
 * Time: 12:02
 */

namespace ArrowWorker;


class Config
{
    private static $Path      = null;
    private static $Config    = []
    private static $ConfigMap = [];
    private static $ConfigExt = '.php';

    private function _Init()
    {
        if( is_null($Path) )
        {
            self::$Path = APP_PATH . DIRECTORY_SEPARATOR . APP_CONFIG_FOLDER . DIRECTORY_SEPARATOR;
        }
    }

    public static function Get( $key=null, $entrance=APP_CONFIG_FILE )
    {
        self::_Init();
        if( count( self::$Config ) == 0 )
        {
            //load main configuration
            self::$Config = self::Load( $entrance );
            //Load extra configuration
            if( isset( self::$Config['user'] ) && count( self::$Config['user'] ) >0 )
            {
                foreach( self::$Config['user'] as $eachExtraConfig )
                {
                    $extraConfig = self::Load( $eachExtraConfig );
                    self::$Config = array_merge( self::$Config, $extraConfig );
                }
            }
        }

        return ( !is_null[$key] && isset(self::$Config[$key]) ) ? self::$Config[$key] : self::$Config;
    }

    private function Load( $fileName )
    {
        if( isset( self::$configMap[$fileName] ) )
        {
            return self::$configMap[$fileName];
        }
        else
        {
            self::$ConfigMap[$fileName] = require( self::$Path.$fileName.self::$ConfigExt );
            return self::$ConfigMap[$fileName];
        }

    }

}
