<?php
/**
 * User: louis
 * Time: 19-10-17 Pm 12:38
 */

namespace ArrowWorker\Server;

use \Swoole\WebSocket\Server as WebSocket;
use \Swoole\WebSocket\Frame;
use \Swoole\Http\Request as SwRequest;
use \Swoole\Http\Response as SwResponse;

use \ArrowWorker\Web\Router;
use \ArrowWorker\Web\Response;
use \ArrowWorker\Web\Request;

use ArrowWorker\Log;
use ArrowWorker\Component;
use ArrowWorker\App;


/**
 * Class Ws
 * @package ArrowWorker\Server
 */
class Ws
{

    /**
     * @var string
     */
    private $_host = '0.0.0.0';

    /**
     * @var int
     */
    private $_port = 8888;

    /**
     * @var int
     */
    private $_mode = SWOOLE_PROCESS;

    /**
     * @var int
     */
    private $_reactorNum = 2;

    /**
     * @var int
     */
    private $_workerNum = 1;

    /**
     * @var bool
     */
    private $_enableCoroutine = true;

    /**
     * @var string
     */
    private $_404 = '';

    /**
     * @var string
     */
    private $_user = 'www';

    /**
     * @var string
     */
    private $_group = 'www';

    /**
     * @var int
     */
    private $_backlog = 1024000;

    /**
     * @var bool
     */
    private $_isEnableStatic = true;

    /**
     * @var string
     */
    private $_documentRoot = '';

    /**
     * @var string
     */
    private $_sslCertFile = '';

    /**
     * @var string
     */
    private $_sslKeyFile = '';

    /**
     * @var int
     */
    private $_maxRequest = 10000;

    /**
     * @var int
     */
    private $_maxCoroutine = 1000;

    /**
     * @var bool
     */
    private $_isEnableCORS = true;

    /**
     * @var bool
     */
    private $_isEnableHttp2 = false;

    /**
     * @var int
     */
    private $_pipeBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    private $_socketBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    private $_maxContentLength = 1024 * 1024 * 10;

    /**
     * @var array
     */
    private $_components = [];

    /**
     * @var string
     */
    private $_handlerOpen = '';

    /**
     * @var string
     */
    private $_handlerMessage = '';

    /**
     * @var string
     */
    private $_handlerClose = '';

    /**
     * @var WebSocket
     */
    private $_server;

    /**
     * @param array $config
     */
    public static function Start( array $config )
    {
        $server = new self( $config );
        $server->_initServer();
        $server->_setConfig();
        $server->_onStart();
        $server->_onOpen();
        $server->_onMessage();
        $server->_onWorkerStart();
        $server->_onRequest();
        $server->_onClose();
        $server->_start();
    }

    /**
     * Http constructor.
     * @param array $config
     */
    private function __construct( array $config )
    {
        $this->_port            = $config['port'] ?? 8888;
        $this->_reactorNum      = $config[ 'reactorNum' ] ?? 2;
        $this->_workerNum       = $config[ 'workerNum' ] ?? 2;
        $this->_enableCoroutine = $config[ 'enableCoroutine' ] ?? true;
        $this->_404             = $config[ '404' ] ?? '';
        $this->_user            = $config[ 'user' ] ?? 'root';
        $this->_group           = $config[ 'group' ] ?? 'root';
        $this->_backlog         = $config[ 'backlog ' ] ?? 1024 * 100;
        $this->_isEnableStatic  = $config[ 'isEnableStatic' ] ?? false;
        $this->_documentRoot    = $config[ 'documentRoot' ] ?? '';
        $this->_sslCertFile     = $config[ 'sslCertFile' ] ?? '';
        $this->_sslKeyFile      = $config[ 'sslKeyFile' ] ?? '';
        $this->_maxRequest      = $config[ 'maxRequest' ] ?? 1000;
        $this->_maxCoroutine    = $config[ 'maxCoroutine' ] ?? 1000;
        $this->_isEnableCORS     = $config[ 'isEnableCORS' ] ?? true;;
        $this->_isEnableHttp2   = $config[ 'isEnableHttp2' ] ?? false;;
        $this->_pipeBufferSize   = $config[ 'pipeBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_socketBufferSize = $config[ 'socketBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_maxContentLength = $config[ 'maxContentLength' ] ?? 1024 * 1024 * 10;
        $this->_components       = $config[ 'components' ] ?? [];
        $controller = App::GetController();
        $this->_handlerOpen      = $controller.($config['handler']['open'] ?? '');
        $this->_handlerMessage   = $controller.($config['handler']['message'] ?? '');
        $this->_handlerClose     = $controller.($config['handler']['close'] ?? '');
        Router::Init( $this->_404 );
    }

    /**
     *
     */
    private function _start()
    {
        $this->_server->start();
    }


    /**
     *
     */
    private function _initServer()
    {


        $this->_server = new WebSocket(
            $this->_host,
            $this->_port,
            $this->_mode,
            $this->_isSsl() ?  SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP
        );
    }

    private function _isSsl()
    {
        if( !file_exists($this->_sslCertFile) || !file_exists($this->_sslKeyFile) )
        {
            return false;
        }
        return true;
    }

    /**
     *
     */
    private function _onStart()
    {
        $this->_server->on( 'start', function ( $server ) {
            Log::Dump( "[   Ws    ] : {$this->_port} started" );
        } );
    }

    /**
     *
     */
    private function _onOpen()
    {
        $this->_server->on('open', function(WebSocket $server, SwRequest $request)  {
            Component::InitOpen($request);
            ($this->_handlerOpen)($server, $request->fd);
            Component::Release(App::TYPE_WEBSOCKET);
        });
    }

    /**
     *
     */
    private function _onMessage()
    {
        $this->_server->on('message', function(WebSocket $server, Frame $frame)  {
            Component::Init();
            ($this->_handlerMessage)($server, $frame);
            Component::Release(App::TYPE_BASE);
        });
    }

    /**
     *
     */
    private function _onClose()
    {
        $this->_server->on('close',   function(WebSocket $server, int $fd) {
            Component::Init();
            ($this->_handlerClose)($server, $fd);
            Component::Release(App::TYPE_BASE);
        });
    }

    /**
     *
     */
    private function _onWorkerStart()
    {
        $this->_server->on( 'WorkerStart', function () {
            Response::SetCORS( (bool)$this->_isEnableCORS );
            Component::InitPool( $this->_components );
        } );
    }

    /**
     *
     */
    private function _onRequest()
    {
        $this->_server->on( 'request', function ( SwRequest $request, SwResponse $response )
        {
            Component::InitWeb($request, $response);
            Router::Exec();
            Component::Release(App::TYPE_HTTP);;
        } );
    }


    /**
     *
     */
    private function _setConfig()
    {
        $this->_server->set([
            'worker_num'            => $this->_workerNum,
            'daemonize'             => false,
            'backlog'               => $this->_backlog,
            'user'                  => $this->_user,
            'group'                 => $this->_group,
            'package_max_length'    => $this->_maxContentLength,
            'enable_static_handler' => $this->_isEnableStatic,
            'reactor_num'           => $this->_reactorNum,
            'pipe_buffer_size'      => $this->_pipeBufferSize,
            'socket_buffer_size'    => $this->_socketBufferSize,
            'max_request'           => $this->_maxRequest,
            'enable_coroutine'      => $this->_enableCoroutine,
            'max_coroutine'         => $this->_maxCoroutine,
            'document_root'         => $this->_documentRoot,
            'log_file'              => Log::$StdoutFile,
            'ssl_cert_file'         => $this->_sslCertFile,
            'ssl_key_file'          => $this->_sslKeyFile,
            'mode'                  => $this->_mode,
            'open_http2_protocol'   => $this->_isEnableHttp2,
        ]);
    }


}