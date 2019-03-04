<?php
/**
 * Created by PhpStorm.
 * User: louis
 * Date: 19-3-5
 * Time: 上午12:29
 */

namespace ArrowWorker\Web;

use \Swoole\WebSocket\Server;
use \Swoole\Http\Request;

class Ws
{

    public static function Open(Server $server, Request $request)
    {

    }

    public static function Message(Server $server, Frame $frame)
    {

    }

    public static function Close(Server $server, int $fd)
    {

    }

}