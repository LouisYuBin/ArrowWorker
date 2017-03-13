<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 15:51
 */

namespace ArrowWorker;
use ArrowWorker\Factory;
use ArrowWorker\Config;

class Load
{
    private static $Class  = [];

    //Load model created by user
    public static function Model( $name )
    {
        $key = 'M'.$name.
        return self::AppModule( $key );
    }

    //Load class created by user
    public static function Lib( $name )
    {
        $key = 'C'.$name;
        return self::AppModule( $key, APP_Class_FOLDER );
    }

    //return app module
    private static function AppModule( $key, $type=APP_Model_FOLDER )
    {
        if( isset( self::$Class[$key] ) )
        {
            return self::$Class[$key];
        }
        else
        {   
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.$name;
            self::$Class[$key] = new $class( $config );
            return self::$Class[$key];
        }
    }

    //Load Frame Component
    public static function Component( $componentName )
    {
        $componentName = ucfirst( $componentName );
        $componentConf = Config::Get( $componentName );
        Factory::$componentName( $componentConf );
    }

    public static function Lang()
    {
        
    }

}
