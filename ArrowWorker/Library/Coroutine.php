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
    public static function Id():int
    {
        return (int)Co::getuid();
    }

    /**
     * @param callable $function
     */
    public static function Create(callable $function)
    {
        Co::create($function);
    }

    /**
     * @param float $seconds
     */
    public static function Sleep(float $seconds):void
    {
        Co::sleep($seconds);
    }

    /**
     *
     */
    public static function Wait():void
    {
        Event::wait();
    }

    /**
     *
     */
    public static function Init():void
    {
        self::$startTime[self::Id()] = time();
    }

    /**
     *
     */
    public static function Release():void
    {
        unset(self::$startTime[self::Id()]);
    }

    /**
     *
     */
    public static function DumpSlow()
    {
        Co::create(function () {
            while (true) {
                $currentTime = time();
                foreach (Co::list() as $eachCo) {
                    var_dump($eachCo);
                    if ($eachCo < 2 || !isset(self::$startTime[$eachCo])) {
                        continue;
                    }

                    if (1 > ($currentTime - self::$startTime[$eachCo])) {
                        continue;
                    }

                    $backTrace = Co::getBackTrace($eachCo);
                    if (false == $backTrace) {
                        continue;
                    }
                    var_dump($backTrace);

                }
                self::Sleep(1);
            }

        });
    }

    public static function Enable(bool $isEnable = true, int $flag = SWOOLE_HOOK_ALL):void
    {
        Runtime::enableCoroutine($isEnable, $flag);
    }

    public static function FileWrite($handle, string $data, $length = null): bool
    {
        for ($i = 0; $i < 3; $i++) {
            if (Co::fwrite($handle, $data, $length)) {
                return true;
            }
        }
        return false;
    }

}