<?php
/**
 * By yubin at 2019-09-11 10:53.
 */

namespace ArrowWorker\Driver\Db;

use Swoole\Coroutine\Channel as swChan;

use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Lib\Coroutine;
use ArrowWorker\Pool as ConnPool;


/**
 * Class Pool
 * @package ArrowWorker\Driver\Db
 */
class Pool implements ConnPool
{
    /**
     *
     */
    const LOG_NAME          = 'Db';

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
     * @var array
     */
    private static $_chanConnections = [

    ];

    /**
     * @var array $appConfig specified keys and pool size
     * check config and initialize connection chan
     */
    public static function Init(array $appConfig) : void
    {
        self::_initConfig($appConfig);
        self::InitPool();
    }

    /**
     * @param array $appConfig specified keys and pool size
     */
    private static function _initConfig( array $appConfig)
    {
        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Critical( 'incorrect config file', self::LOG_NAME );
            return ;
        }

        foreach ( $config as $index => $value )
        {
            if( !isset($appConfig[$index]) )
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
                Log::Critical( "configuration for {$index} is incorrect. config : ".json_encode($value), self::LOG_NAME );
                continue;
            }

            $value['poolSize']     = (int)$appConfig[$index]>0 ? $appConfig[$index] : self::DEFAULT_POOL_SIZE;
            $value['connectedNum'] = 0;

            self::$_configs[$index] = $value;
            self::$_pool[$index]    = new swChan( $value['poolSize'] );
        }
    }


    /**
     * initialize connection pool
     */
    public static function InitPool()
    {
        foreach (self::$_configs as $index=>$config)
        {
            for ($i=$config['connectedNum']; $i<$config['poolSize']; $i++)
            {
                $driver = "ArrowWorker\\Driver\\Db\\".$config['driver'];
                $conn = new $driver( $config );
                if( false===$conn->InitConnection() )
                {
                    Log::Critical("initialize connection failed, config : {$index}=>".json_encode($config), self::LOG_NAME);
                    continue ;
                }
                self::$_configs[$index]['connectedNum']++;
                self::$_pool[$index]->push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
     */
    public static function GetConnection( $alias = 'default' )
    {
        $coId = Coroutine::Id();
        if( isset(self::$_chanConnections[$coId][$alias]) )
        {
            return self::$_chanConnections[$coId][$alias];
        }

        if( !isset(self::$_pool[$alias] ) )
        {
            return false;
        }

        $retryTimes = 0;
        _RETRY:
        $conn = self::$_pool[$alias]->pop( 0.2 );
        if ( false === $conn )
        {
            if( self::$_configs[$alias]['connectedNum']<self::$_configs[$alias]['poolSize'] )
            {
                self::InitPool();
                goto _RETRY;
            }

            if( $retryTimes<=2 )
            {
                $retryTimes++;
                Log::Warning("get ( {$alias} : {$retryTimes} ) connection failed, retrying...",self::LOG_NAME);
                goto _RETRY;
            }
        }
        self::$_chanConnections[$coId][$alias] = $conn;
        return $conn;
    }

    /**
     * @return void
     */
    public static function Release() : void
    {
        $coId = Coroutine::Id();
        if( !isset(self::$_chanConnections[$coId]) )
        {
            return ;
        }

        foreach ( self::$_chanConnections[$coId] as $alias=>$connection )
        {
            self::$_pool[$alias]->push( $connection );
        }
        unset(self::$_chanConnections[$coId], $coId);
    }


}