<?php
/**
 * User: Louis
 * Date: 2016/8/3 15:51
 * Update Record:
 *      2017-07-24 By Louis
 */

namespace ArrowWorker;

use function PHPSTORM_META\elementType;

class Loader
{
    private static $appClass    = [];
    private static $ArrowDriver = [];


    //Load model created by user
    public static function Model( $name )
    {
        return self::_appModule( APP_MODEL_FOLDER.$name, $name, APP_MODEL_FOLDER );
    }

    //Load class created by user
    public static function Classes( $name )
    {
        return self::_appModule( APP_CLASS_FOLDER.$name, $name, APP_CLASS_FOLDER );
    }

    //Load logical class
    public static function Service( $name )
    {
        return self::_appModule( APP_SERVICE_FOLDER.$name, $name, APP_SERVICE_FOLDER );
    }

    //return app module
    private static function _appModule( $key, $name, $type=APP_MODEL_FOLDER )
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

    public static function Db( $alias )
    {
        $type = "Db";
        return self::_arrowDriver( $type, $alias);
    }

    public static function Cache( $alias )
    {
        $type = "Cache";
        return self::_arrowDriver( $type, $alias);
    }

    public static function Daemon( $alias )
    {
        $type = "Daemon";
        return self::_arrowDriver( $type, $alias);
    }

    private static function _arrowDriver($driverType, $alias)
    {
        $key  = $driverType.$alias;
        if( isset( self::$ArrowDriver[$key] ) )
        {
            return self::$ArrowDriver[$key];
        }
        else
        {
            $driver = self::$ArrowDriver[$key] = Factory::$driverType( $alias );
            if( $driver == null )
            {
                throw new \Exception("driver {$driverType}::$alias does not exists.");
                exit;
            }
        }
        return self::$ArrowDriver[$key];
    }

    public static function Lang()
    {
        
    }

}
