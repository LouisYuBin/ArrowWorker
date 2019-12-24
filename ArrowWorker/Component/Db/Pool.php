<?php
/**
 * By yubin at 2019-09-11 10:53.
 */

namespace ArrowWorker\Component\Db;

use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\PoolInterface as ConnPool;


/**
 * Class Pool
 * @package ArrowWorker\Component\Db
 */
class Pool implements ConnPool
{
    /**
     *
     */
    const LOG_NAME          = 'Db';

    const MODULE_NAME = "DbPool";

    /**
     *
     */
    const CONFIG_NAME       = 'Db';

    /**
     *
     */
    const DEFAULT_DRIVER = 'Mysqli';

    /**
     * @var array
     */
    private static $_pool   = [];

    /**
     * @var array
     */
    private static $_configs = [];

    /**
     * @param array $appAlias
     * @param array $config
     */
    public static function Init(array $appAlias, array $config=[]) : void
    {
        self::_initConfig($appAlias, $config);
        self::_initPool();
    }

    /**
     * @param array $appAlias specified keys and pool size
     * @param array $config
     */
    private static function _initConfig( array $appAlias, array $config)
    {
        if( count($config)>0 )
        {
            goto INIT;
        }

        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Dump( __CLASS__.'::'.__FUNCTION__." incorrect config file", Log::TYPE_WARNING, self::MODULE_NAME );
            return ;
        }

        INIT:
        foreach ( $config as $index => $value )
        {
            if( !isset($appAlias[$index]) )
            {
                //initialize specified db config only
                continue ;
            }

            //ignore incorrect config
            if (
                !isset( $value['driver'] )   ||
                !in_array($value['driver'], ['Mysqli', 'Pdo']) ||
                !isset( $value['host'] )     ||
                !isset( $value['dbName'] )   ||
                !isset( $value['userName'] ) ||
                !isset( $value['password'] ) ||
                !isset( $value['port'] )     ||
                !isset( $value['charset'] )
            )
            {
                Log::Dump( __CLASS__.'::'.__FUNCTION__." incorrect configuration. {$index}=> ".json_encode($value), Log::TYPE_WARNING, self::MODULE_NAME );
                continue;
            }

            $value['poolSize']     = (int)$appAlias[$index]>0 ? $appAlias[$index] : self::DEFAULT_POOL_SIZE;
            $value['connectedNum'] = 0;

            self::$_configs[$index] = $value;
            self::$_pool[$index]    = SwChan::Init( $value['poolSize'] );
        }
    }


    /**
     * initialize connection pool
     */
    public static function _initPool()
    {
        foreach (self::$_configs as $index=>$config)
        {
            for ($i=$config['connectedNum']; $i<$config['poolSize']; $i++)
            {
                $driver = __NAMESPACE__.'\\'.$config['driver'];
                $conn = new $driver( $config );
                if( false===$conn->InitConnection() )
                {
                    Log::Dump(__CLASS__.'::'.__FUNCTION__." {$driver}->InitConnection connection failed, config : {$index}=>".json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME );
                    continue ;
                }
                self::$_configs[$index]['connectedNum']++;
                self::$_pool[$index]->Push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
     */
    public static function Get( string $alias = 'default' )
    {
    	$context = Coroutine::GetContext();
        if( isset($context[__CLASS__][$alias]) )
        {
            return $context[__CLASS__][$alias];
        }

        if( !isset(self::$_pool[$alias] ) )
        {
            return false;
        }

        $retryTimes = 0;
        _RETRY:
        $conn = self::$_pool[$alias]->Pop( 0.2 );
        if ( false === $conn )
        {
            if( self::$_configs[$alias]['connectedNum']<self::$_configs[$alias]['poolSize'] )
            {
                self::_initPool();
            }

            if( $retryTimes<=2 )
            {
                $retryTimes++;
                Log::Dump(__CLASS__.'::'.__FUNCTION__." get connection( {$alias} : {$retryTimes} ) failed, retrying...", Log::TYPE_WARNING, self::MODULE_NAME);
                goto _RETRY;
            }
        }
	    $context[__CLASS__][$alias] = $conn;
        return $conn;
    }

    /**
     * @return void
     */
    public static function Release() : void
    {
        $context = Coroutine::GetContext();
        if( !isset($context[__CLASS__]) )
        {
            return ;
        }

        foreach ( $context[__CLASS__] as $alias=>$connection )
        {
            self::$_pool[$alias]->Push( $connection );
        }
    }


}