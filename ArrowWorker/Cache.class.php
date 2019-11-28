<?php
/**
 * By yubin at 2019-09-19 17:53.
 */

namespace ArrowWorker;

use ArrowWorker\Component\Cache\Pool;
use ArrowWorker\Component\Cache\Redis;

class Cache
{

    const DEFAULT_ALIAS = 'default';


    /**
     * @param array $config
     */
    public static function Init( array $config )
    {
        Pool::Init($config);
    }

    /**
     * @param string $alias
     * @return false|Redis
     */
    public static function Get( string $alias=self::DEFAULT_ALIAS )
    {
        return Pool::GetConnection($alias);
    }

    public static function Release()
    {
        Pool::Release();
    }
}