<?php
/**
 * By yubin at 2019-12-17 17:20.
 */

namespace ArrowWorker\Library;

use Swoole\Coroutine as Co;

/**
 * Class Context
 * @package ArrowWorker\Library
 */
class Context
{

    /**
     * @param string $key
     * @param $value
     */
    public static function set(string $key, $value):void
    {
        Co::getContext()[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return Co::getContext()[$key];
    }

    /**
     * @param string $key
     * @param $value
     * @return mixed
     */
    public static function fill(string $key, $value)
    {
        return Co::getContext()[$key][] = $value;
    }

    /**
     * @param string $key
     * @param string $subKey
     * @param $value
     * @return mixed
     */
    public static function subSet(string $key, string $subKey, $value)
    {
        return Co::getContext()[$key][$subKey] = $value;
    }

    /**
     * @param string $key
     * @param string $subKey
     * @return mixed
     */
    public static function getSub(string $key, string $subKey)
    {
        return Co::getContext()[$key][$subKey];
    }

    /**
     * @return mixed
     */
    public static function getContext()
    {
        return Co::getContext();
    }
}