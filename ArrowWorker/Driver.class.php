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
 * 框架驱动加载类
 * Class Driver
 * @package ArrowWorker
 */
class Driver
{
	/**
	 * @var string 驱动器所在目录
	 */
	const DRIVER_DIR = 'ArrowWorker\\Driver';

    /**
     * 缓存驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\Cache\Redis
     */
    public static function Cache( string $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    /**
     * channel 驱动
     * @param string $alias
     * @return \ArrowWorker\Driver\Channel\Queue
     */
    public static function Channel( string $alias='app' )
    {
        return self::_init(__FUNCTION__, $alias);
    }

    /**
     * _init 加载框架驱动器
     * @author Louis
     * @param string $driverType
     * @param string $alias
     * @return \ArrowWorker\Driver\Channel\Queue
     */
    protected static function _init(string $driverType, string $alias)
    {
        $config = Config::Get($driverType);
        if ( !isset( $config[$alias] ) )
        {
            Log::Error("driver {$driverType}->{$alias} config does not exists.");
        }
        $class = self::DRIVER_DIR.'\\'.$driverType."\\".$config[$alias]['driver'];
        return $class::Init( $config[$alias], $alias );
    }


}
