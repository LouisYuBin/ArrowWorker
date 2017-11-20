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
    private static $appClass    = [];

    //Load model created by user
    public static function Model( $name )
    {
        return self::_appModule( $name, APP_MODEL_FOLDER );
    }

    //Load class created by user
    public static function Classes( $name )
    {
        return self::_appModule( $name, APP_CLASS_FOLDER );
    }

    //Load logical class
    public static function Service( $name )
    {
        return self::_appModule( $name, APP_SERVICE_FOLDER );
    }

    //return app module
    private static function _appModule( $name, $type=APP_MODEL_FOLDER )
    {
        $key = $type.$name;
        if( !isset( self::$appClass[$key] ) )
        {
            $class  = '\\'.APP_FOLDER.'\\'.$type.'\\'.ucfirst( $name );
            self::$appClass[$key] = new $class;
        }
        return self::$appClass[$key];
    }

    public static function Lang()
    {
        
    }

}
