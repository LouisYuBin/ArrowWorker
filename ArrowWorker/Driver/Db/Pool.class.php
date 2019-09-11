<?php
/**
 * By yubin at 2019-09-11 10:53.
 */

namespace ArrowWorker\Driver\Db;


use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Swoole;
use Swoole\Coroutine\Channel as swChan;

/**
 * Class Pool
 * @package ArrowWorker\Driver\Db
 */
class Pool
{
    /**
     *
     */
    const LOG_NAME          = 'Db';

    /**
     *
     */
    const DEFAULT_POOL_SIZE = 10;

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
    public static function Init(array $appConfig)
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
                Log::Error( "configuration for {$index} is incorrect. config : ".json_encode($value), self::LOG_NAME );
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
                $driver = "ArrowWorker\\Driver\\Db\\".$config['driver'];
                $conn = (new $driver( $config ))->_initConnection();
                if( false==$conn )
                {
                    Log::Warning("initialize mysqli connection failed, config : {$index}=>".json_encode($config), self::LOG_NAME);
                    continue ;
                }
                self::$pool[$index]->push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Mysqli|Pdo
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

        _RETRY:
        $conn = self::$pool[$alias]->pop( 1 );
        if ( false === $conn )
        {
            goto _RETRY;
        }
        self::$chanConnections[$coId][$alias] = $conn;
        return $conn;
    }

    /**
     * @return void
     */
    public static function ReturnConnection()
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

    /**
     * @param string $alias
     * @return string
     */
    public static function GetDriverName( string $alias)
    {
        return isset( self::$configs[$alias]) ? self::$configs[$alias]['driver'] : self::DEFAULT_DRIVER;
    }

}