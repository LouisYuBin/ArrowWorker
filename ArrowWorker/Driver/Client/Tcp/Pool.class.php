<?php
/**
 * By yubin at 2019-10-05 11:05.
 */

namespace ArrowWorker\Driver\Client\Tcp;


use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Swoole;
use Swoole\Coroutine\Channel as swChan;
use ArrowWorker\Driver\PoolInterface;
use ArrowWorker\Driver\Pool as ConnPool;

class Pool extends ConnPool implements PoolInterface
{
    /**
     *
     */
    const LOG_NAME          = 'TcpClient';


    /**
     *
     */
    const CONFIG_NAME       = 'TcpClient';

    /**
     *
     */
    const DEFAULT_DRIVER    = 'Redis';


    /**
     * @param array $appConfig specified keys and pool size
     */
    protected static function _initConfig( array $appConfig)
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
                !isset( $value['driver'] ) ||
                !in_array($value['driver'], ['Redis', 'Memcached'] ) ||
                !isset( $value['host'] )   ||
                !isset( $value['port'] )   ||
                !isset( $value['password'] )
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
                $driver = "ArrowWorker\\Driver\\Cache\\".$config['driver'];
                $conn = new $driver( $config );
                if( false===$conn->InitConnection() )
                {
                    Log::Warning("initialize connection failed, config : {$index}=>".json_encode($config), self::LOG_NAME);
                    continue ;
                }
                self::$pool[$index]->push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Redis
     */
    public static function GetConnection( $alias = 'default' )
    {
        $coId = Swoole::GetCid();
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
        $coId = Swoole::GetCid();
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