<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker;

use \Swoole\Coroutine as Co;
use \Swoole\Http\Server as Http;
use \Swoole\Server;


class Swoole
{
    public static $defaultHttpConfig = [
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'pipeBufferSize'   => 1024*1024*100,
        'socketBufferSize' => 1024*1024*100,
        'maxRequest'       => 20000,
        'reactorNum'       => 4,
        'maxContentLength' => 2088960,
        'maxCoroutine'     => 10000,
        'enableCoroutine'  => true,
        'enableStaticHandler' => false,
        'documentRoot'     => ''
    ];

    public static $defaultTcpConfig = [
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'pipeBufferSize'   => 1024*1024*100,
        'socketBufferSize' => 1024*1024*100,
        'enableCoroutine'  => true,
        'maxRequest'       => 20000,
        'reactorNum'       => 4,
        'maxContentLength' => 2088960,
        'maxCoroutine'     => 10000,
        'mode'             => SWOOLE_PROCESS
    ];

    private static function _getHttpConfig(array $config) : array
    {
        $config = array_merge(static::$defaultHttpConfig, $config);
        return [
            'port'       => $config['port'],
            'worker_num' => $config['workerNum'],
            'daemonize'  => false,
            'backlog'    => $config['backlog'],
            'package_max_length'    => $config['maxContentLength'],
            'enable_static_handler' => $config['enableStaticHandler'],
            'reactor_num'        => $config['reactorNum'],
            'pipe_buffer_size'   => $config['pipeBufferSize'],
            'socket_buffer_size' => $config['socketBufferSize'],
            'max_request'        => $config['maxRequest'],
            'enable_coroutine'   => $config['enableCoroutine'],
            'max_coroutine'      => $config['maxCoroutine'],
            'document_root'      => $config['documentRoot'],
            'log_file'           => Log::$StdoutFile
        ];
    }

    private static function _getTcpConfig(array $config) : array
    {
        $config = array_merge(static::$defaultHttpConfig, $config);
        return [
            'port'       => $config['port'],
            'worker_num' => $config['workerNum'],
            'daemonize'  => false,
            'backlog'    => $config['backlog'],
            'reactor_num'        => $config['reactorNum'],
            'pipe_buffer_size'   => $config['pipeBufferSize'],
            'socket_buffer_size' => $config['socketBufferSize'],
            'max_request'        => $config['maxRequest'],
            'enable_coroutine'   => $config['enableCoroutine'],
            'max_coroutine'      => $config['maxCoroutine'],
            'log_file'           => Log::$StdoutFile
        ];
    }

    public static function StartHttpServer(array $config)
    {
        $config = static::_getHttpConfig($config);
        Router::Init();
        $server = new Http("0.0.0.0", $config['port']);
        $server->set($config);
        $server->on('Request', function($request, $response) {
            Cookie::Init(is_array($request->cookie) ? $request->cookie : [], $response);
            Request::Init(
                is_array($request->get)   ? $request->get : [],
                is_array($request->post) ? $request->post : [],
                is_array($request->server) ? $request->server : [],
                is_array($request->files) ? $request->files : []
            );
            Session::Reset();
            Response::Init($response);
            Router::Go();
        });

        $server->start();
    }

    public static function StartWebsocketServer(array $config)
    {
        $config = static::_getTcpConfig($config);
        $server = new Server('0.0.0.0',$config['port'], $config['mode'], SWOOLE_SOCK_TCP);
        $server->set($config);
        $server->on('connect', $config['handler']['connect']);
        $server->on('receive', $config['handler']['receive']);
        $server->on('close',   $config['handler']['close']);
        $server->start();
    }


    public static function StartTcpServer(array $config)
    {

    }

    public static function StartUdpServer(array $config)
    {

    }

    /**
     * get swoole coroutine id
     * @return int
     */
    public static function GetCid() : int
    {
        return Co::getuid();
    }
}