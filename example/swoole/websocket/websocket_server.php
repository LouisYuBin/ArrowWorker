<?php
$serv = new Swoole\Websocket\Server("127.0.0.1", 9503);

$serv->on('Open', function($server, $req) {
echo "connection open: ".$req->fd.PHP_EOL;
});

$serv->on('Message', function($server, $frame) {
    $output = "From Server:".date("Y-m-d h:i:s");
    echo "message: ".$frame->data.PHP_EOL;
    $server->push($frame->fd, $output);
});

$serv->on('Close', function($server, $fd) {
echo "connection close: ".$fd.PHP_EOL;
});

$serv->start();