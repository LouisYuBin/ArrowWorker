<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Library\Channel as SwChan;

use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\PoolInterface as ConnPool;

class Pool implements ConnPool
{

    const LOG_NAME    = 'Cache';

    const MODULE_NAME = "CachePool";


    const CONFIG_NAME       = 'Cache';


    const DEFAULT_DRIVER    = 'Redis';

    /**
     * @var array
     */
    private static $_pool   = [];

    /**
     * @var array
     */
    private static $_configs = [];

    /**
     * @param array $appAlias specified keys and pool size
     * @param array $config
     */
    public static function Init(array $appAlias, array $config=[]) : void
    {
        self::initConfig($appAlias, $config);
        self::initPool();
    }

    /**
     * @param array $appAlias specified keys and pool size
     * @param array $config
     */
    private static function initConfig( array $appAlias, array $config=[])
    {
        if( count($config)>0 )
        {
            goto INIT;
        }

        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Dump( 'incorrect config file', Log::TYPE_WARNING, self::MODULE_NAME );
            return ;
        }

        INIT:
        foreach ( $config as $index => $value )
        {
            if( !isset($appAlias[$index]) )
            {
                continue ;
            }

            if (
                !isset( $value['driver'] ) ||
                !in_array($value['driver'], ['Redis', 'Memcached'] ) ||
                !isset( $value['host'] )   ||
                !isset( $value['port'] )   ||
                !isset( $value['password'] )
            )
            {
                Log::Dump( __CLASS__.'::'.__FUNCTION__."incorrect configuration . {$index}=>".json_encode($value), Log::TYPE_WARNING, self::MODULE_NAME );
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
    public static function initPool()
    {
        foreach (self::$_configs as $index=>$config)
        {
            for ($i=$config['connectedNum']; $i<$config['poolSize']; $i++)
            {
                $driver = __NAMESPACE__."\\".$config['driver'];
                $conn = new $driver( $config );
                if( false===$conn->InitConnection() )
                {
                    Log::Dump(__CLASS__.'::'.__FUNCTION__." InitConnection failed, config : {$index}=>".json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME );
                    continue ;
                }
                self::$_configs[$index]['connectedNum']++;
                self::$_pool[$index]->Push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Redis
     */
    public static function Get( string $alias = 'default' )
    {
        $coId = Coroutine::Id();
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
                self::initPool();
            }
            
            if( $retryTimes<=2 )
            {
                $retryTimes++;
                Log::Dump(__CLASS__.'::'.__FUNCTION__." get connection( {$alias} : {$retryTimes} ) failed,retrying...", Log::TYPE_WARNING, self::MODULE_NAME );
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