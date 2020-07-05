<?php
/**
 * By yubin at 2019-12-17 17:20.
 */

namespace ArrowWorker\Library;

use Swoole\Coroutine as Co;

class Context
{

    public static function Set(string $key, $value)
    {
        Co::getContext()[$key] = $value;
    }

    public static function Get(string $key)
    {
        return Co::getContext()[$key];
    }

    public static function Fill(string $key, $value)
    {
        return Co::getContext()[$key][] = $value;
    }

    public static function SubSet(string $key, string $subKey, $value)
    {
        return Co::getContext()[$key][$subKey] = $value;
    }

    public static function GetSub(string $key, string $subKey)
    {
        return Co::getContext()[$key][$subKey];
    }

    public static function GetInstance()
    {
        return Co::getContext();
    }
}