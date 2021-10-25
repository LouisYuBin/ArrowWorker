<?php
/**
 * By yubin at 2019/2/28 6:53 PM.
 */

namespace App\Controller\Demo;

use ArrowWorker\Log\Log;
use ArrowWorker\Memory;
use Swoole\WebSocket\Frame as WebSocketFrame;
use Swoole\WebSocket\Server as WebSocketServer;


class WebSocket
{

    public static function Open(WebSocketServer $server, int $fd)
    {
        $memory = Memory::get('default');
        //$memory->Write('spicy',['id'=>3,'token'=>'5566666']);
        $map = $memory->Read('louis');
        $map1 = $memory->Read('spicy');
        //var_dump($map,$map1);
        /*       var_dump( $memory->Write($req->fd, $req->fd) );
               var_dump($memory->IsKeyExists($req->fd));*/
        Log::info("connection open: {$fd}");
    }

    public static function Message(WebSocketServer $server, WebSocketFrame $frame)
    {
        $memory = Memory::get('default');
        $memory->Write('spicy', [
            'id'    => 3,
            'token' => '5566666',
        ]);
        $map = $memory->Read('louis');
        //$memory = Memory::Get('clients');
        //var_dump($memory->IsKeyExists($frame->fd));
        Log::info("received message: {$frame->fd}_{$frame->data}");
        Log::info($server->push(1, json_encode([
            "hello",
            "world",
            mt_rand(1, 10000),
        ])));

        //$server->push($frame->fd, json_encode(["hello", "world"]));
    }

    public static function Close(WebSocketServer $server, int $fd)
    {
        Log::info("connection close: {$fd}");
    }


}