<?php
/**
 * By yubin at 2019-10-05 11:05.
 */

namespace ArrowWorker\Client\Tcp;


use ArrowWorker\Config;
use ArrowWorker\Log;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Channel as SwChan;
use ArrowWorker\PoolInterface as ConnPool;

class Pool implements ConnPool
{

    const LOG_NAME          = 'TcpClient';

    const CONFIG_NAME       = 'TcpClient';
    
    const MODULE_NAME = "[ TcpPool ] ";

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
     * @param  array $appAlias specified keys and pool size
     * @param  array $config
     */
    public static function Init(array $appAlias, array $config=[]) : void
    {
        self::_initConfig($appAlias, $config);
        self::InitPool();
    }

    /**
     * @param array $appAlias specified keys and pool size
     * @param array $config
     */
    protected static function _initConfig( array $appAlias, array $config)
    {
        if( count($config)>0 )
        {
            goto INIT;
        }

        $config = Config::Get( self::CONFIG_NAME );
        if ( !is_array( $config ) || count( $config ) == 0 )
        {
            Log::Dump( 'load config file failed', Log::TYPE_WARNING, self::MODULE_NAME );
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
                !isset( $value['host'] ) ||
                !isset( $value['port'] )
            )
            {
                Log::Dump( "configuration for {$index} is incorrect. config : ".json_encode($value), Log::TYPE_WARNING,self::MODULE_NAME );
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
    public static function InitPool()
    {
        foreach (self::$_configs as $index=>$config)
        {
            for ($i=$config['connectedNum']; $i<$config['poolSize']; $i++)
            {
                $conn = Client::Init( $config['host'], $config['port'] );
                if( false===$conn->IsConnected() )
                {
                    Log::Dump("initialize connection failed, config : {$index}=>".json_encode($config), Log::TYPE_WARNING, self::MODULE_NAME );
                    continue ;
                }
                self::$_configs[$index]['connectedNum']++;
                self::$_pool[$index]->Push( $conn );
            }
        }
    }

    /**
     * @param string $alias
     * @return false|Client
     */
    public static function GetConnection( string $alias = 'default' )
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
                self::InitPool();
            }

            if( $retryTimes<=2 )
            {
                $retryTimes++;
                Log::Warning("get ( {$alias} : {$retryTimes} ) connection failed.",self::LOG_NAME);
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