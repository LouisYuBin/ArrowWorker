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
    private static $driverDir = 'ArrowWorker\\Driver';

    public static function Db( $config )
    {
        $class = self::$driverDir.'\\Db\\'.$config['driver'];
        return $class::initDb( $config );
    }

    public static function Cache( $config )
    {
        $class = self::$driverDir.'\\Cache\\'.$config['driver'];
        return $class::initCache( $config );
    }

    public static function Daemon( $config )
    {
        $class = self::$driverDir.'\\Daemon\\'. $config['driver'];
        return $class::initDaemon( $config );
    }

    public static function View( $config )
    {
        $class = self::$driverDir.'\\View';
        return $class::initView( $config );
    }
}
