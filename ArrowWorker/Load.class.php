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
    const ModelPre = 'M_';
    const LibPre = 'C_';
    private static $Class  = [];


    //Load model created by user
    public static function Model( $name )
    {
        $key = ModelPre.$name;
        return self::AppModule( $key, $name );
    }

    //Load class created by user
    public static function Lib( $name )
    {
        $key = LibPre.$name;
        return self::AppModule( $key, $name, APP_ClASS_FOLDER );
    }

    //return app module
    private static function AppModule( $key, $name, $type=APP_MODEL_FOLDER )
    {
        if( isset( self::$Class[$key] ) )
        {
            return self::$Class[$key];
        }
        else
        {   
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.$name;
            self::$Class[$key] = new $class;
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
