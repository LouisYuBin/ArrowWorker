<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker;

use \Swoole\Coroutine as Co;
use \Swoole\Http\Server as Http;
use \Swoole\Server as SocketServer;
use \Swoole\WebSocket\Server as WebSocket;

use \ArrowWorker\Web\Response;
use \ArrowWorker\Web\Request;
use \ArrowWorker\Web\Router;
use \ArrowWorker\Web\Session;
use \ArrowWorker\Web\Cookie;

class Swoole
{
    const WEB_SERVER        = 1;
    const WEB_SOCKET_SERVER = 2;
    const TCP_SERVER        = 3;
    const UDP_SERVER        = 4;

    const CONTROLLER_NAMESPACE = '\\'.APP_DIR.'\\'.APP_CONTROLLER_DIR.'\\';

    public static $defaultHttpConfig = [
        'host'      => '0.0.0.0',
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
        'sslCertFile'      => '',
        'sslKeyFile'       => '',
        'documentRoot'     => '',
        'mode'             => SWOOLE_PROCESS

    ];

    public static $defaultTcpConfig = [
        'host'      => '0.0.0.0',
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

    public static $defaultWebSocketConfig = [
        'host'      => '0.0.0.0',
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


    public static $defaultUdpConfig = [
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

    private static function _getConfig(int $type, array $config) : array
    {
        switch ($type)
        {
            case static::WEB_SERVER:
                $defaultConfig = static::$defaultHttpConfig;
                break;
            case static::WEB_SOCKET_SERVER:
                $defaultConfig = static::$defaultWebSocketConfig;
                break;
            case static::TCP_SERVER:
                $defaultConfig = static::$defaultTcpConfig;
                break;
            default:
                $defaultConfig = static::$defaultUdpConfig;
        }
        $config = array_merge($defaultConfig, $config);
        $config['enableSsl'] = empty($config['sslCertFile']) ? false : true;

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
            'log_file'           => Log::$StdoutFile,
            'handler'            => $config['handler'],
            'ssl_cert_file'      => $config['sslCertFile'],
            'ssl_key_file'       => $config['sslKeyFile'],
            'mode'               => $config['mode'],
        ];
    }

    public static function StartHttpServer(array $config)
    {
        Router::Init();
        $config = static::_getConfig(static::WEB_SERVER, $config);
        $server = new Http($config['host'], $config['port'], $config['mode'],  empty($config['ssl_cert_file']) ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_TCP| SWOOLE_SSL);
        $server->set($config);
        $server->on('start', function($server) use ($config) {
            Log::Dump("Http server is listening at port : ".$config['port']);
        });
        $server->on('request', function(\Swoole\Http\Request $request, \Swoole\Http\Response $response) {
            Cookie::Init(is_array($request->cookie) ? $request->cookie : []);
            Request::Init(
                is_array($request->get)   ? $request->get : [],
                is_array($request->post) ? $request->post : [],
                is_array($request->server) ? $request->server : [],
                is_array($request->files) ? $request->files : [],
                    is_array($request->header) ? $request->header : []
            );
            Session::Reset();
            Response::Init($response);
            Router::Go();

            Cookie::Release();
            Request::Release();
            Response::Release();
            Memory::Release();
        });

        $server->start();
    }

    public static function StartWebSocketServer(array $config)
    {
        Router::Init();
        $config = static::_getConfig(static::WEB_SOCKET_SERVER, $config);

        $server = new WebSocket($config['host'], $config['port'],  $config['mode'], empty($config['ssl_cert_file']) ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_TCP| SWOOLE_SSL);
        $server->set($config);
        $server->on('start', function($server) use ($config) {
            Log::Dump("Websocket server is listening at port : ".$config['port']);
        });
        $server->on('open', static::CONTROLLER_NAMESPACE.$config['handler']['open']);
        $server->on('message', static::CONTROLLER_NAMESPACE.$config['handler']['message']);
        $server->on('request', function($request, $response) {
            Cookie::Init(is_array($request->cookie) ? $request->cookie : []);
            Request::Init(
                is_array($request->get)   ? $request->get : [],
                is_array($request->post) ? $request->post : [],
                is_array($request->server) ? $request->server : [],
                is_array($request->files) ? $request->files : [],
                is_array($request->header) ? $request->header : []

            );
            Session::Reset();
            Response::Init($response);
            Router::Go();

            Cookie::Release();
            Request::Release();
            Response::Release();
            Memory::Release();
        });
        $server->on('close',   static::CONTROLLER_NAMESPACE.$config['handler']['close']);
        $server->start();
    }


    public static function StartTcpServer(array $config)
    {
        $config = static::_getConfig(static::TCP_SERVER, $config);
        $server = new SocketServer($config['host'], $config['port'], $config['mode'], SWOOLE_SOCK_TCP);
        $server->set($config);
        $server->on('connect', static::CONTROLLER_NAMESPACE.$config['handler']['connect']);
        $server->on('receive', static::CONTROLLER_NAMESPACE.$config['handler']['receive']);
        $server->on('close',   static::CONTROLLER_NAMESPACE.$config['handler']['close']);
        $server->start();
    }

    public static function StartUdpServer(array $config)
    {
        $config = static::_getConfig(static::UDP_SERVER, $config);
        $server = new SocketServer($config['host'], $config['port'], $config['mode'], SWOOLE_SOCK_UDP);
        $server->set($config);
        $server->on('connect', static::CONTROLLER_NAMESPACE.$config['handler']['connect']);
        $server->on('receive', static::CONTROLLER_NAMESPACE.$config['handler']['receive']);
        $server->on('close',   static::CONTROLLER_NAMESPACE.$config['handler']['close']);
        $server->start();
    }

    /**
     * get swoole coroutine id
     * @return int
     */
    public static function GetCid() : int
    {
        return (int)(posix_getpid().Co::getuid());
    }
}