<?php
/**
 * User: Louis
 * Date: 2016/8/3
 * Time: 10:00
 * Update Records:
 *     2017-03-13 By Louis
 */

namespace ArrowWorker;

class Factory
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
        foreach ($config as $eachConfig)
        {
            if( $alias==$eachConfig['alias'] )
            {
                $class = $namespace.'\\'.$eachConfig['driver'];
                return $class::init( $eachConfig );
            }
        }
        return null;
    }
}
