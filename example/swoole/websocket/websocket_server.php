<?php
$serv = new Swoole\Websocket\Server("127.0.0.1", 9503);

$serv->on('Open', function($server, $req) {
    $echo =  "({$req->fd})进村了";
    echo $echo. PHP_EOL;
    foreach($server->connections as $fd)
    {
        // 循环发送给每一个$fd，不能单纯的echo
        $server->push($fd, $echo);
    }
});

$serv->on('Message', function( $server,  $frame) {
    echo "message: ".$frame->data.PHP_EOL;
    $msg = "({$frame->fd}) 说:".$frame->data;
    foreach($server->connections as $fd)
    {
        // 循环发送给每一个$fd，不能单纯的echo
        $server->push($fd, $msg);
    }
    echo "当前服务器共有 ".count($server->connections). " 个连接\n";
});

$serv->on('Close', function( $server,  $frame) {
    $echo =  "({$frame->fd})跑路了";
    echo $echo. PHP_EOL;
    foreach($server->connections as $fd)
    {
        // 循环发送给每一个$fd，不能单纯的echo
        $server->push($fd, $echo);
    }
});


$serv->start();

