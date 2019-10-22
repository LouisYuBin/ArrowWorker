<?php
/**
 * By yubin at 2019-10-18 14:04.
 */

namespace ArrowWorker\Lib;


use Swoole\Coroutine as Co;
use Swoole\Event;

/**
 * Class Coroutine
 * @package ArrowWorker
 */
class Coroutine
{

    /**
     * @var array
     */
    private static $_startTime = [];

    /**
     * @return int
     */
    public static function Id()
    {
        return (int)Co::getuid();
    }

    /**
     * @param callable $function
     */
    public static function Create( callable $function)
    {
        Co::create($function);
    }

    /**
     * @param int $seconds
     */
    public static function Sleep( int $seconds)
    {
        Co::sleep($seconds);
    }

    /**
     *
     */
    public static function Wait()
    {
        Event::wait();
    }

    /**
     *
     */
    public static function Init()
    {
        self::$_startTime[ self::Id() ] = time();
    }

    /**
     *
     */
    public static function Release()
    {
        unset( self::$_startTime[self::Id()] );
    }

    /**
     *
     */
    public static function DumpSlow()
    {
        Co::create(function (){
            while ( true )
            {
                $currentTime = time();
                foreach ( Co::list() as $eachCo)
                {
                    var_dump($eachCo);
                    if( $eachCo<2 || !isset( self::$_startTime[$eachCo] )  )
                    {
                        continue;
                    }

                    if( 1>($currentTime - self::$_startTime[$eachCo]) )
                    {
                        continue;
                    }

                    $backTrace = Co::getBackTrace($eachCo);
                    if( false==$backTrace )
                    {
                        continue;
                    }
                    var_dump($backTrace);

                }
                self::Sleep(1);
            }

        });
    }

}