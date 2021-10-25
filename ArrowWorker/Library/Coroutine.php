<?php
/**
 * By yubin at 2019-10-18 14:04.
 */

namespace ArrowWorker\Library;


use Swoole\Coroutine as Co;
use Swoole\Event;
use Swoole\Runtime;

/**
 * Class Coroutine
 * @package ArrowWorker
 */
class Coroutine
{

    /**
     * @var array
     */
    private static array $startTime = [];

    /**
     * @return int
     */
    public static function id():int
    {
        return (int)Co::getuid();
    }

    /**
     * @param callable $function
     */
    public static function create(callable $function)
    {
        Co::create($function);
    }

    /**
     * @param float $seconds
     */
    public static function sleep(float $seconds):void
    {
        Co::sleep($seconds);
    }

    /**
     *
     */
    public static function wait():void
    {
        Event::wait();
    }

    /**
     *
     */
    public static function init():void
    {
        self::$startTime[self::id()] = time();
    }

    /**
     *
     */
    public static function release():void
    {
        unset(self::$startTime[self::id()]);
    }

    /**
     * @param bool $isEnable
     * @param int $flag
     */
    public static function enable(bool $isEnable = true, int $flag = SWOOLE_HOOK_ALL):void
    {
        Runtime::enableCoroutine($isEnable, $flag);
    }

    /**
     * @param $handle
     * @param string $data
     * @param null $length
     * @return bool
     */
    public static function writeFile($handle, string $data, $length = null): bool
    {
        for ($i = 0; $i < 3; $i++) {
            if (Co::fwrite($handle, $data, $length)) {
                return true;
            }
        }
        return false;
    }

}