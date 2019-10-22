<?php
/**
 * By yubin at 2019-10-05 11:07.
 */

namespace ArrowWorker\Client\Ws;

use ArrowWorker\Config;
use ArrowWorker\Pool as ConnPool;
use ArrowWorker\Log;
use ArrowWorker\Lib\Coroutine;

use Swoole\Coroutine\Channel as swChan;

class Pool implements ConnPool
{
    /**
     *
     */
    const LOG_NAME          = 'WsClient';


    /**
     *
     */
    const CONFIG_NAME       = 'WsClient';

    /**
     * @var array
     */
    private static $pool   = [];

    /**
     * @var array
     */
    private static $configs = [];

    /**
     * @var array
     */
    private static $chanConnections = [

    ];

    /**
     * @var array $appConfig specified keys and pool size
     * check config and initialize connection chan
     */
    public static function Init(array $appConfig) : void
    {
        self::_initConfig($appConfig);
        self::_initPool();
    }

    /**
     * @param array $appConfig specified keys and pool size
     */
    private static function _initConfig( array $appConfig)
    {
        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Error( 'incorrect config file', self::LOG_NAME );
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
                !isset( $value['host'] ) ||
                !isset( $value['port'] ) ||
                !isset( $value['uri'] ) ||
                !isset( $value['isSsl'])
            )
            {
                Log::Warning( "configuration for {$index} is incorrect. config : ".json_encode($value), self::LOG_NAME );
                continue;
            }

            $value['poolSize'] = (int)$appConfig[$index]>0 ? $appConfig[$index] : self::DEFAULT_POOL_SIZE;

            self::$configs[$index] = $value;
            self::$pool[$index]    = new swChan( $value['poolSize'] );
        }
    }


    /**
     * initialize connection pool
     */
    private static function _initPool()
    {
        foreach (self::$configs as $index=>$config)
        {
            for ($i=self::$pool[$index]->length(); $i<$config['poolSize']; $i++)
            {
                $wsClient = Client::Init( $config['host'], $config['port'], $config['uri'], $config['isSsl'] );
                if( false===$wsClient->Upgrade() )
                {
                    Log::Warning("initialize connection failed, config : {$index}=>".json_encode($config), self::LOG_NAME);
                    continue ;
                }
                self::$pool[$index]->push( $wsClient );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Client
     */
    public static function GetConnection( $alias = 'default' )
    {
        $coId = Coroutine::Id();
        if( isset(self::$chanConnections[$coId][$alias]) )
        {
            return self::$chanConnections[$coId][$alias];
        }

        if( !isset(self::$pool[$alias] ) )
        {
            return false;
        }

        $retryTimes = 0;
        _RETRY:
        $conn = self::$pool[$alias]->pop( 0.5 );
        if ( false === $conn && $retryTimes<=2 )
        {
            $retryTimes++;
            Log::Warning("get ( {$alias} : {$retryTimes} ) connection failed.",self::LOG_NAME);
            goto _RETRY;
        }
        self::$chanConnections[$coId][$alias] = $conn;
        return $conn;
    }

    /**
     * @return void
     */
    public static function Release() : void
    {
        $coId = Coroutine::Id();
        if( !isset(self::$chanConnections[$coId]) )
        {
            return ;
        }

        foreach ( self::$chanConnections[$coId] as $alias=>$connection )
        {
            self::$pool[$alias]->push( $connection );
        }
        unset(self::$chanConnections[$coId], $coId);
    }

}