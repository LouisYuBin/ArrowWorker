<?php
/**
 * By yubin at 2019/3/4 3:31 PM.
 */

namespace ArrowWorker;

use \ArrowWorker\Driver\Channel\Queue;


class Chan extends Driver
{
    /**
     *
     */
    const COMPONENT_TYPE = 'Channel';

    /**
     *
     */
    const DEFAULT_ALIAS = 'default';


    /**
     * @param string $alias
     * @return Queue;
     */
    public static function Get($alias=self::DEFAULT_ALIAS) : Queue
    {
        static::_init(static::COMPONENT_TYPE, $alias);
    }
}