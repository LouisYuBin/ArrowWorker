<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker\Server;

use \Swoole\Server as SocketServer;

use ArrowWorker\App;
use ArrowWorker\Log;
use ArrowWorker\Component;


/**
 * Class Swoole
 * @package ArrowWorker
 */
class Udp
{

    /**
     * @var array
     */
    public static $defaultConfig = [
        'port'      => 8888,
        'workerNum' => 4,
        'backlog'   => 1000,
        'user'      => 'root',
        'group'     => 'root',
        'pipeBufferSize'   => 1024*1024*100,
        'socketBufferSize' => 1024*1024*100,
        'enableCoroutine'  => true,
        'maxRequest'       => 20000,
        'reactorNum'       => 4,
        'maxContentLength' => 2088960,
        'maxCoroutine'     => 10000,
        'mode'             => SWOOLE_PROCESS
    ];

    /**
     * @param int   $type
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
        $config = static::_getConfig( $config );
        $server = new SocketServer($config['host'], $config['port'], $config['mode'], SWOOLE_SOCK_UDP);
        $server->set($config);
        $server->on('start', function() use ($config) {
            Log::Dump("[   Udp   ] : {$config['port']} started.");
        });
        $server->on('WorkerStart', function() use ($config) {
            Component::CheckInit($config);
        });
        $server->on('connect', function(SocketServer $server, int $fd) use ( $config ) {
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['connect'];
            Log::Init();
            $function($server, $fd);
            Log::Release();
            Component::Release(2);
        });
        $server->on('receive', function(SocketServer $server, int $fd, int $reactor_id, string $data) use ($config) {
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['receive'];
            Log::Init();
            $function($server, $fd, $data);
            Component::Release(2);
        });
        $server->on('close',   function(SocketServer $server, int $fd) use ($config) {
            $function = App::CONTROLLER_NAMESPACE.$config['handler']['close'];
            Log::Init();
            $function($server, $fd);
            Component::Release(2);
        });
        $server->start();
    }

}