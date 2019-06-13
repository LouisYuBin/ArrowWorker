<?php
/**
 * By yubin at 2019/3/4 3:31 PM.
 */

namespace ArrowWorker;

use \ArrowWorker\Driver\Channel;
use \ArrowWorker\Driver\Channel\Queue;

class Chan extends Driver
{
    /**
     *
     */
    const COMPONENT_TYPE = 'Chan';

    /**
     *
     */
    const DEFAULT_ALIAS = 'default';


    /**
     * @param string $alias
     * @return \ArrowWorker\Driver\Channel\Queue;
     */
    public static function Get($alias=self::DEFAULT_ALIAS) : Queue
    {
        $config = Config::Get(static::COMPONENT_TYPE);
        if ( !isset( $config[$alias] ) || !is_array($config[$alias]) )
        {
            Log::Error(" Chan->{$alias} config does not exists/config format incorrect.");
        }
        return Channel::Init( $config[$alias], $alias );
    }
}