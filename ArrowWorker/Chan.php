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

    const MODULE_NAME  = 'Chan';

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
        if ( isset( self::$pool[$alias] ) )
        {
            return self::$pool[$alias];  //channel is already been initialized
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
                Log::Dump( "{$alias} config does not exists/is not array.", Log::TYPE_WARNING ,self::MODULE_NAME);
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
            Log::Dump( "msg_remove_queue result : " . $eachQueue->Close(), Log::TYPE_DEBUG ,self::MODULE_NAME );
        }
    }

}