<?php

namespace ArrowWorker;

use ArrowWorker\Driver\Channel\Queue;

/**
 * Class Message
 */
class Chan
{

    const CONFIG_NAME = 'Chan';

    const DEFAULT_CONFIG = [
        //default message size bytes
        'msgSize' => 128,
        //default channel buffer size bytes
        'bufSize' => 10240000
    ];
    /**
     * 消息实例连接池
     * @var array
     */
    protected static $pool = [];

    /**
     * 消息配置
     * @var array
     */
    protected static $config = [];

    /**
     * Get 初始化 对外提供
     * @author Louis
     * @param string $alias
     * @param array  $userConfig
     * @return Queue
     */
    public static function Get( string $alias = 'default', array $userConfig = [] )
    {
        if ( isset( static::$pool[$alias] ) )
        {
            //channel is already been initialized
            return static::$pool[$alias];
        }

        if( 0==count($userConfig) )
        {
            $configs = Config::Get( self::CONFIG_NAME );
            if ( isset( $configs[$alias] ) && is_array( $configs[$alias] ) )
            {
                $userConfig = $configs[$alias];
            }
            else
            {
                Log::Dump( " Channel::Init : {$alias} config does not exists/is not array." );
            }
        }

        $config = array_merge( self::DEFAULT_CONFIG, $userConfig );
        self::$pool[$alias] = new Queue( $config, $alias );

        return static::$pool[$alias];
    }

    /**
     * Close 关闭管道
     * @author Louis
     */
    public static function Close()
    {
        foreach ( self::$pool as $eachQueue )
        {
            Log::Dump( "msg_remove_queue result : " . $eachQueue->Close() );
        }
    }

}