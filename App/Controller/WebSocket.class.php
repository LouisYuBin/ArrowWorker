<?php
/**
 * By yubin at 2019/2/28 6:53 PM.
 */

namespace App\Controller;

use ArrowWorker\Log;
use ArrowWorker\Memory;
use \Swoole\WebSocket\Server as WebSocketServer;
use \Swoole\WebSocket\Frame  as WebSocketFrame;


class WebSocket
{

    public static function Open(WebSocketServer $server, \Swoole\Http\Request $req)
    {
        $memory = Memory::Get('default');
        //$memory->Write('spicy',['id'=>3,'token'=>'5566666']);
        $map = $memory->Read('louis');
        $map1 = $memory->Read('spicy');
        //var_dump($map,$map1);
 /*       var_dump( $memory->Write($req->fd, $req->fd) );
        var_dump($memory->IsKeyExists($req->fd));*/
        Log::Info( "connection open: {$req->fd}");
    }

    public static function Message(WebSocketServer $server, WebSocketFrame $frame)
    {
        $memory = Memory::Get('default');
        $memory->Write('spicy',['id'=>3,'token'=>'5566666']);
        $map = $memory->Read('louis');
        //$memory = Memory::Get('clients');
        //var_dump($memory->IsKeyExists($frame->fd));
        Log::Info( "received message: {$frame->fd}_{$frame->data}");
        Log::Info($server->push(1, json_encode(["hello", "world",mt_rand(1,10000)])));

        //$server->push($frame->fd, json_encode(["hello", "world"]));
    }

    public static function Close(WebSocketServer $server, int $fd)
    {
        Log::Info( "connection close: {$fd}");
    }



}