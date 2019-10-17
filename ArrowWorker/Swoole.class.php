<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker;

use \Swoole\Coroutine as Co;


/**
 * Class Swoole
 * @package ArrowWorker
 */
class Swoole
{

    /**
     * get swoole coroutine id
     * @return int
     */
    public static function GetCid() : int
    {
        return (int)Co::getuid();
    }

}