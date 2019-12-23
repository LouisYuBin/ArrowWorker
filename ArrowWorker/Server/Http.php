<?php

/**
 * User: louis
 * Time: 19-10-17 下午12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\Library\Process;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Http\Server;

use ArrowWorker\Server\Server as ServerPattern;
use ArrowWorker\Web\Router;

use ArrowWorker\Log;
use ArrowWorker\App;



/**
 * Class Http
 * @package ArrowWorker\Server
 */
class Http extends ServerPattern
{
	
	const MODULE_NAME = 'Http Server';

    /**
     * @var string
     */
    private $_404 = '';

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
     * @var bool
     */
    private $_isEnableCORS = true;

    /**
     * @var bool
     */
    private $_isEnableHttp2 = false;

    /**
     * @var Router
     */
    private $_router;

    /**
     * @param array $config
     */
    public static function Start( array $config )
    {
        $server = new self( $config );
        $server->_initServer();
        $server->_initComponent(App::TYPE_HTTP);
        $server->_initRouter();
        $server->_setConfig();
        $server->_onStart();
        $server->_onWorkerStart();
        $server->_onRequest();
        $server->_start();
    }

    /**
     * Http constructor.
     * @param array $config
     */
    private function __construct( array $config )
    {
        $this->_port            = $config[ 'port' ] ?? 8080;
        $this->_mode            = $config['mode'] ?? SWOOLE_PROCESS;
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
        $this->_isEnableCORS    = $config[ 'isEnableCORS' ] ?? true;;
        $this->_isEnableHttp2   = $config[ 'isEnableHttp2' ] ?? false;;
        $this->_pipeBufferSize   = $config[ 'pipeBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_socketBufferSize = $config[ 'socketBufferSize' ] ?? 1024 * 1024 * 100;
        $this->_maxContentLength = $config[ 'maxContentLength' ] ?? 1024 * 1024 * 10;
        $this->_components       = $config[ 'components' ] ?? [];
        $this->_identity         = $config['identity'];
    }

    private function _start()
    {
        $this->_server->start();
    }

    private function _initServer()
    {
        if ( !file_exists( $this->_sslCertFile ) || !file_exists( $this->_sslKeyFile ) )
        {
            $this->_sslCertFile = '';
            $this->_sslKeyFile  = '';
        }

        $this->_server = new Server(
            $this->_host,
            $this->_port,
            $this->_mode,
            $this->_isSsl() ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP
        );
    }

    private function _initRouter()
    {
        $this->_router = Router::Init( $this->_404 );
    }

    /**
     * @return bool
     */
    private function _isSsl()
    {
        if ( !file_exists( $this->_sslCertFile ) || !file_exists( $this->_sslKeyFile ) )
        {
            return false;
        }
        return true;
    }

    private function _onStart()
    {
        $this->_server->on( 'start', function ( $server )
        {
	        Process::SetName("{$this->_identity}_Http:{$this->_port} Manager");
            Log::Dump( "listening at port {$this->_port}",Log::TYPE_DEBUG, self::MODULE_NAME );
        } );
    }

    private function _onWorkerStart()
    {
        $this->_server->on( 'WorkerStart', function ()
        {
        	Process::SetName("{$this->_identity}_Http:{$this->_port} Worker");
            $this->_component->InitWebWorkerStart( $this->_components, (bool)$this->_isEnableCORS );
        } );
    }

    private function _onRequest()
    {
        $this->_server->on( 'request', function ( SwRequest $request, SwResponse $response )
        {
            $this->_component->InitWebRequest( $request, $response );
            $this->_router->Go();
            $this->_component->Release();
        } );
    }

    private function _setConfig()
    {
        $options = [
            'worker_num'          => $this->_workerNum,
            'daemonize'           => false,
            'backlog'             => $this->_backlog,
            'user'                => $this->_user,
            'group'               => $this->_group,
            'package_max_length'  => $this->_maxContentLength,
            'reactor_num'         => $this->_reactorNum,
            'pipe_buffer_size'    => $this->_pipeBufferSize,
            'socket_buffer_size'  => $this->_socketBufferSize,
            'max_request'         => $this->_maxRequest,
            'enable_coroutine'    => $this->_enableCoroutine,
            'max_coroutine'       => $this->_maxCoroutine,
            'log_file'            => Log::GetStdOutFilePath(),
            'mode'                => $this->_mode,
            'open_http2_protocol' => $this->_isEnableHttp2,
            'ssl_cert_file'       => $this->_sslCertFile,
            'ssl_key_file'        => $this->_sslKeyFile,
            'hook_flags'          => SWOOLE_HOOK_TCP
        ];


        if ( $this->_isEnableStatic && file_exists( $this->_documentRoot ) )
        {
            $options[ 'enable_static_handler' ] = $this->_isEnableStatic;
            $options[ 'document_root' ]         = $this->_documentRoot;
        }

        $this->_server->set( $options );
    }

}