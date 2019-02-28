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
        Log::Info( "connection open: {$req->fd}\n");
    }

    public static function Message(WebSocketServer $server, WebSocketFrame $frame)
    {
        Log::Info( "received message: {$frame->data}\n");
        $server->push($frame->fd, json_encode(["hello", "world"]));
    }

    public static function Close(WebSocketServer $server, int $fd)
    {
        Log::Info( "connection close: {$fd}");
    }



}