<?php
/**
 * By yubin at 2019/2/28 6:53 PM.
 */

namespace App\Controller;

use ArrowWorker\Log;
use \Swoole\WebSocket\Server as WebSocketServer;
use \Swoole\WebSocket\Frame  as WebSocketFrame;


class WebSocket
{

    public static function Open(WebSocketServer $server, $req)
    {
        Log::Info( "connection open: {$req->fd}");
    }

    public static function Message(WebSocketServer $server, WebSocketFrame $frame)
    {
        Log::Info( "received message: {$frame->fd}_{$frame->data}");
        Log::Info($server->push(1, json_encode(["hello", "world",mt_rand(1,10000)])));

        //$server->push($frame->fd, json_encode(["hello", "world"]));
    }

    public static function Close(WebSocketServer $server, int $fd)
    {
        Log::Info( "connection close: {$fd}");
    }



}