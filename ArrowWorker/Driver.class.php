<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 10:00
 * Update Records:
 *     2017-03-13 By Louis
 *     2017-10-30 By Louis
 *     2017-11-08 By Louis
 */

namespace ArrowWorker;

class Driver
{
    private static $driverDir   = 'ArrowWorker\\Driver';

    public static function Db( $alias )
    {
        return self::_init(self::$driverDir.'\\Db', $alias, 'db');
    }

    public static function Cache( $alias )
    {
        return self::_init(self::$driverDir.'\\Cache', $alias, 'cache');
    }

    public static function Daemon( $alias )
    {
        return self::_init(self::$driverDir.'\\Daemon', $alias, 'daemon');
    }

    public static function View( $alias )
    {
        $config = Config::App('view');
        $class  = self::$driverDir.'\\View';
        return $class::_init( $config );
    }

    private static function _init($namespace, $alias, $configKey)
    {
        $config = Config::App($configKey);
        if ( isset( $config[$alias] ) )
        {
            $class = $namespace.'\\'.$config[$alias]['driver'];
            return $class::init( $config[$alias], $alias );
        }
        else
        {
            throw new \Exception("driver {$configKey}::{$alias} does not exists.");
        }

    }

}
