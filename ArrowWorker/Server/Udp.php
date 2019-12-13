<?php
/**
 * User: louis
 * Time: 18-5-10 下午12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\Library\Process;
use \Swoole\Server;

use ArrowWorker\App;
use ArrowWorker\Log;
use ArrowWorker\Component;
use ArrowWorker\Server\Server as ServerPattern;


/**
 * Class Udp
 * @package ArrowWorker\Server
 */
class Udp extends ServerPattern
{

    /**
     * @var int|mixed
     */
    private $_heartbeatCheckInterval = 30;


    /**
     * @var int|mixed
     */
    private $_heartbeatIdleTime = 60;

    /**
     * @var bool|mixed
     */
    private $_openEofCheck = true;


    /**
     * @var mixed|string
     */
    private $_packageEof = '\r\n';

    /**
     * @var mixed|string
     */
    private $_openEofSplit = '\r\n';

    /**
     * @var string
     */
    private $_handlerConnect = '';

    /**
     * @var string
     */
    private $_handlerReceive = '';

    /**
     * @var string
     */
    private $_handlerClose = '';

    /**
     * @var bool
     */
    private $_isUdp6 = false;

    /**
     * @param array $config
     */
    public static function Start( array $config )
    {
        $server = new self( $config );
        $server->_initServer();
        $server->_initComponent(App::TYPE_UDP);
        $server->_setConfig();
        $server->_onStart();
        $server->_onWorkerStart();

        $server->_onConnect();
        $server->_onReceive();
        $server->_onClose();
        $server->_start();
    }

    /**
     * Http constructor.
     * @param array $config
     */
    private function __construct( array $config )
    {
        $this->_port             = $config[ 'port' ] ?? 8083;
        $this->_reactorNum       = $config[ 'reactorNum' ] ?? 2;
        $this->_workerNum        = $config[ 'workerNum' ] ?? 2;
        $this->_enableCoroutine  = $config[ 'enableCoroutine' ] ?? true;
        $this->_user             = $config[ 'user' ] ?? 'root';
        $this->_group            = $config[ 'group' ] ?? 'root';
        $this->_backlog          = $config[ 'backlog ' ] ?? 1024 * 100;
        $this->_maxCoroutine     = $config[ 'maxCoroutine' ] ?? 1000;
        $this->_pipeBufferSize   = $config[ 'pipeBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_socketBufferSize = $config[ 'socketBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_maxContentLength = $config[ 'maxContentLength' ] ?? 1024 * 1024 * 10;

        $this->_heartbeatCheckInterval = $config[ 'heartbeatCheckInterval' ] ?? 60;
        $this->_heartbeatIdleTime      = $config[ 'heartbeatIdleTime' ] ?? 30;
        $this->_openEofCheck           = $config[ 'openEofCheck' ] ?? false;
        $this->_openEofSplit           = $config[ 'openEofSplit' ] ?? false;
        $this->_packageEof             = $config[ 'packageEof' ] ?? '\r\n';

        $this->_components = $config[ 'components' ] ?? [];

        $controller = App::GetController();
        $this->_handlerConnect = $controller.($config[ 'handler' ]['connect'] ?? '');
        $this->_handlerReceive = $controller.($config[ 'handler' ]['receive'] ?? '');
        $this->_handlerClose   = $controller.($config[ 'handler' ]['close'] ?? '');

        $this->_isUdp6 = $config[ 'isUdp6' ] ?? false;
	
	    $this->_identity         = $config['identity'];
	
    }

    private function _start()
    {
        $this->_server->start();
    }

    private function _initServer()
    {
        $this->_server = new Server(
            $this->_host,
            $this->_port,
            $this->_mode,
            (bool)$this->_isUdp6 ? SWOOLE_SOCK_UDP6 : SWOOLE_SOCK_UDP );
    }

    private function _onStart()
    {
        $this->_server->on( 'start', function ( $server )
        {
	        Process::SetName("{$this->_identity}_Udp:{$this->_port} Manager");
	        Log::Dump( "[   Tcp   ] : {$this->_port} started" );
        } );
    }

    private function _onConnect()
    {
        $this->_server->on( 'connect', function ( Server $server, int $fd )
        {
            $this->_component->InitCommon();
            ($this->_handlerConnect)( $server, $fd );
            $this->_component->Release();
        } );
    }

    private function _onReceive()
    {
        $this->_server->on( 'receive', function ( Server $server, int $fd, int $reactor_id, string $data )
        {
            $this->_component->InitCommon();
            ($this->_handlerReceive)( $server, $fd, $data );
            $this->_component->Release();
        } );
    }

    private function _onClose()
    {
        $this->_server->on( 'close', function ( Server $server, int $fd )
        {
            $this->_component->InitCommon();
            ($this->_handlerClose)( $server, $fd );
            $this->_component->Release();
        } );
    }

    private function _onWorkerStart()
    {
        $this->_server->on( 'WorkerStart', function ()
        {
	        Process::SetName("{$this->_identity}_Udp:{$this->_port} Worker");
	        $this->_component->InitPool( $this->_components );
        } );
    }

    private function _setConfig()
    {
        $this->_server->set( [
            'mode'                     => $this->_mode,
            'worker_num'               => $this->_workerNum,
            'daemonize'                => false,
            'backlog'                  => $this->_backlog,
            'user'                     => $this->_user,
            'group'                    => $this->_group,
            'package_max_length'       => $this->_maxContentLength,
            'reactor_num'              => $this->_reactorNum,
            'pipe_buffer_size'         => $this->_pipeBufferSize,
            'socket_buffer_size'       => $this->_socketBufferSize,
            'enable_coroutine'         => $this->_enableCoroutine,
            'max_coroutine'            => $this->_maxCoroutine,
            'log_file'                 => Log::$StdoutFile,
            'heartbeat_check_interval' => $this->_heartbeatCheckInterval,
            'heartbeat_idle_time'      => $this->_heartbeatIdleTime,
            'open_eof_check'           => $this->_openEofCheck,
            'package_eof'              => $this->_packageEof,
            'open_eof_split'           => $this->_openEofSplit,
        ] );
    }

}