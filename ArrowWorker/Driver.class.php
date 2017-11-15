<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 10:00
 * Update Records:
 *     2017-03-13 By Louis
 *     2017-10-30 By Louis
 *     2017-11-08 By Louis
 *     2017-11-15 By Louis
 */

namespace ArrowWorker;

class Driver
{
    private static $driverDir   = 'ArrowWorker\\Driver';

    public static function Db( $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    public static function Cache( $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    public static function Daemon( $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    public static function View( $alias='app' )
    {
        $class  = self::$driverDir.'\\View';
        return $class::init( Config::App('view') );
    }

    private static function _init($driverType, $alias)
    {
        $config = Config::App($driverType);
        if ( isset( $config[$alias] ) )
        {
            $driver = self::$driverDir.'\\'.$driverType."\\".$config[$alias]['driver'];
            return $driver::init( $config[$alias], $alias );
        }
        else
        {
            throw new \Exception("driver {$driverType}->{$alias} does not exists.");
        }

    }

}
