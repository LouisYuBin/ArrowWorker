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

/**
 * Class Driver
 * @package ArrowWorker
 */
class Driver
{
	/**
	 * @var string 驱动器所在目录
	 */
	private static $driverDir   = 'ArrowWorker\\Driver';

    /**
     * 数据库驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\Db\Mysqli
     */
    public static function Db(string $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    /**
     * 缓存驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\Cache\Redis
     */
    public static function Cache(string $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    /**
     * ArrowWorker驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\Daemon\ArrowDaemon
     */
    public static function Daemon(string $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    /**
     * 加载view驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\View
     */
    public static function View(string $alias='app' )
    {
        $class  = self::$driverDir.'\\View';
        return $class::init( Config::App('view') );
    }


	/**
	 * _init
	 * @param string $driverType
	 * @param string $alias
	 * @return mixed
	 * @throws \Exception
	 */
	private static function _init(string $driverType, string $alias)
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
