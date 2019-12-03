<?php

namespace ArrowWorker\Component\Cache;

use ArrowWorker\Library\Channel as SwChan;

use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Pool as ConnPool;

class Pool implements ConnPool
{

    const LOG_NAME          = 'Cache';

    const LOP_PREFIX = "[ CachePool ] ";


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
     * @var array
     */
    private static $_connections = [

    ];

    /**
     * @param array $appAlias specified keys and pool size
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
    private static function _initConfig( array $appAlias, array $config=[])
    {
        if( count($config)>0 )
        {
            goto INIT;
        }

        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Dump( '[ CachePool ] incorrect config file' );
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
                Log::Dump( self::LOP_PREFIX."incorrect configuration . {$index}=>".json_encode($value) );
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
                $driver = __NAMESPACE__."\\".$config['driver'];
                $conn = new $driver( $config );
                if( false===$conn->InitConnection() )
                {
                    Log::Dump(self::LOP_PREFIX."initialize connection failed, config : {$index}=>".json_encode($config));
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
    public static function GetConnection( string $alias = 'default' )
    {
        $coId = Coroutine::Id();
        if( isset(self::$_connections[$coId][$alias]) )
        {
            return self::$_connections[$coId][$alias];
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
                Log::Dump(self::LOP_PREFIX."get ( {$alias} : {$retryTimes} ) connection failed,retrying...");
                goto _RETRY;
            }

        }
        self::$_connections[$coId][$alias] = $conn;
        return $conn;
    }

    /**
     * @return void
     */
    public static function Release() : void
    {
        $coId = Coroutine::Id();
        if( !isset(self::$_connections[$coId]) )
        {
            return ;
        }

        foreach ( self::$_connections[$coId] as $alias=>$connection )
        {
            self::$_pool[$alias]->Push( $connection );
        }
        unset(self::$_connections[$coId], $coId);
    }

}