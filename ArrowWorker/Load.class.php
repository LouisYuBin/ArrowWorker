<?php
/**
 * User: Louis
 * Date: 2016/8/3 15:51
 * Update Record:
 *      2017-07-24 By Louis
 */

namespace ArrowWorker;

class Load
{
    const ModelPre = 'm_';
    const LibPre = 'c_';
    private static $appClass  = [];


    //Load model created by user
    public static function Model( $name )
    {
        $key = self::ModelPre.$name;
        return self::AppModule( $key, $name );
    }

    //Load class created by user
    public static function Lib( $name )
    {
        $key = self::LibPre.$name;
        return self::AppModule( $key, $name, APP_CLASS_FOLDER );
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
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.$name;
            self::$appClass[$key] = new $class;
            return self::$appClass[$key];
        }
    }

    //Load Frame Component
    public static function Component( $componentName )
    {
        $componentName = ucfirst( $componentName );
        $componentConf = Config::Frame( $componentName );
        Factory::$componentName( $componentConf );
    }

    public static function Lang()
    {
        
    }

}
