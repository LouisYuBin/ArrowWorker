<?php
/**
 * User: Louis
 * Date: 2016/8/3 15:51
 * Update Record:
 *      2017-07-24 By Louis
 */

namespace ArrowWorker;

class Loader
{
    private static $appClass  = [];


    //Load model created by user
    public static function Model( $name )
    {
        return self::AppModule( APP_MODEL_FOLDER.$name, $name, APP_MODEL_FOLDER );
    }

    //Load class created by user
    public static function Classes( $name )
    {
        return self::AppModule( APP_CLASS_FOLDER.$name, $name, APP_CLASS_FOLDER );
    }

    //Load logical class
    public static function Service( $name )
    {
        return self::AppModule( APP_SERVICE_FOLDER.$name, $name, APP_SERVICE_FOLDER );
    }

    //return app module
    private static function AppModule( $key, $name, $type=APP_MODEL_FOLDER )
    {
        if( isset( self::$appClass[$key] ) )
        {
            return self::$appClass[$key];
        }
        else
        {
            $moduleName = ucfirst( $name );
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.$moduleName;
            self::$appClass[$key] = new $class;
            return self::$appClass[$key];
        }
    }

    //Load Frame Component
    public static function Component( $componentName )
    {
        $componentConf = Config::Arrow( $componentName );
        $componentName = ucfirst( $componentName );
        return Factory::$componentName( $componentConf );
    }

    public static function Lang()
    {
        
    }

}
