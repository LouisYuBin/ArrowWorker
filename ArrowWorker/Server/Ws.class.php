<?php
/**
 * User: louis
 * Time: 19-10-17 Pm 12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\Lib\Coroutine;
use \Swoole\WebSocket\Server as WebSocket;
use \Swoole\WebSocket\Frame;
use \Swoole\Http\Request as SwRequest;
use \Swoole\Http\Response as SwResponse;

use \ArrowWorker\Web\Router;
use \ArrowWorker\Web\Response;
use \ArrowWorker\Web\Request;
use \ArrowWorker\Web\Session;
use \ArrowWorker\Web\Cookie;

use ArrowWorker\Log;
use ArrowWorker\Component;
use ArrowWorker\App;


/**
 * Class Ws
 * @package ArrowWorker
 */
class Ws
{

    /**
     * @var array
     */
    public static $defaultConfig = [
        'host'      => '0.0.0.0',
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'user'      => 'root',
        'group'     => 'root',
        'pipeBufferSize'   => 1024*1024*100,
        'socketBufferSize' => 1024*1024*100,
        'enableCoroutine'  => true,
        'isAllowCORS'      => true,
        'maxRequest'       => 20000,
        'reactorNum'       => 4,
        'maxContentLength' => 2088960,
        'maxCoroutine'     => 10000,
        'sslCertFile'      => '',
        'sslKeyFile'       => '',
        'documentRoot'     => '',
        'mode'             => SWOOLE_PROCESS
    ];


    /**
     * @param array $config
     * @return array
     */
    private static function _getConfig( array $config) : array
    {
        $config = array_merge(self::$defaultConfig, $config);
        return [
            'port'       => $config['port'],
            'worker_num' => $config['workerNum'],
            'daemonize'  => false,
            'backlog'    => $config['backlog'],
            'user'       => $config['user'],
            'group'      => $config['group'],
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
            'components'         => isset($config['components']) ? $config['components'] : [],
            'isAllowCORS'        => isset($config['isAllowCORS']) ? (bool)$config['isAllowCORS'] : false
        ];

    }



    /**
     * @param array $config
     */
    public static function Start( array $config)
    {
        Router::Init( isset($config['404']) ? (string)$config['404'] : '' );
        $config = self::_getConfig( $config );
        $cors   = $config['isAllowCORS'];

        $server = new WebSocket($config['host'], $config['port'],  $config['mode'], empty($config['ssl_cert_file']) ? SWOOLE_SOCK_TCP : SWOOLE_SOCK_TCP| SWOOLE_SSL);
        $server->set($config);
        $server->on('start', function($server) use ( $config ) {
            Log::Dump("[   Ws    ] : {$config['port']} started");
        });
        $server->on('WorkerStart', function() use ( $config ) {
            Component::CheckParams($config);
            //Coroutine::DumpSlow();
        });
        $server->on('open', function(WebSocket $server, SwRequest $request) use ($config) {
            Log::SetLogId();
            Request::Init( $request );
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['open'];
            $function($server, $request->fd);
            Component::Release(2);
        });
        $server->on('message', function(WebSocket $server, Frame $frame) use ( $config ) {
            Coroutine::Init();
            Log::SetLogId();
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['message'];
            $function($server, $frame);
            Component::Release(2);
            Coroutine::Release();
        });
        $server->on('request', function( SwRequest $request, SwResponse $response ) use ($cors) {
            Coroutine::Init();
            Log::SetLogId();
            Response::Init($response, $cors);
            Request::Init($request);
            Cookie::Init(is_array($request->cookie) ? $request->cookie : []);
            Session::Init();
            Router::Exec();
            Component::Release();
            Coroutine::Release();
        });
        $server->on('close',   function(WebSocket $server, int $fd) use ($config) {
            Log::SetLogId();
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['close'];
            $function($server, $fd);
            Component::Release(2);
        });
        $server->start();
    }

}