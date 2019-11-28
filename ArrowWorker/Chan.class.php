<?php

namespace ArrowWorker;

use ArrowWorker\Component\Channel\Queue;

/**
 * Class Message
 */
class Chan
{

    /**
     * channel config file name
     */
    const CONFIG_NAME = 'Chan';

    const LOG_PREFIX  = '[  Chan   ] ';

    /**
     * default config for each channel
     */
    const DEFAULT_CONFIG = [
        'msgSize' => 128,
        'bufSize' => 10240000
    ];

    /**
     * channel pool
     * @var array
     */
    protected static $pool = [];

    /**
     * initialize channel and return channel object
     * @author Louis
     * @param string $alias
     * @param array  $userConfig
     * @return Queue
     */
    public static function Get( string $alias = 'default', array $userConfig = [] )
    {
        if ( isset( static::$pool[$alias] ) )
        {
            return static::$pool[$alias];  //channel is already been initialized
        }

        if ( 0 == count( $userConfig ) )
        {
            $configs = Config::Get( self::CONFIG_NAME );
            if ( isset( $configs[$alias] ) && is_array( $configs[$alias] ) )
            {
                $userConfig = $configs[$alias];
            }
            else
            {
                Log::Dump( self::LOG_PREFIX."{$alias} config does not exists/is not array." );
            }
        }

        self::$pool[$alias] = Queue::Init( array_merge( self::DEFAULT_CONFIG, $userConfig ), $alias );

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
            Log::Dump( self::LOG_PREFIX."msg_remove_queue result : " . $eachQueue->Close() );
        }
    }

}