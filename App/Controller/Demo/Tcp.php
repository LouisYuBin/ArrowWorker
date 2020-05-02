<?php
/**
 * By yubin at 2019/3/22 9:25 AM.
 */

namespace App\Controller\Demo;

use ArrowWorker\Log;
use Swoole\Server as server;

class Tcp
{

    public static function Connect(server $server, int $fd)
    {
        Log::Info("{$fd} connected .");
    }

    public static function Receive(server $server, int $fd, string $data)
    {
        var_dump($data);
    }

    public static function Close(server $server, int $fd)
    {
        Log::Info("{$fd} closed .");
    }

}