<?php
/**
 * User: louis
 * Time: 19-10-17 Pm 12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\Process;
use ArrowWorker\Log;
use ArrowWorker\Server\Server as ServerPattern;
use ArrowWorker\Web\Dispatcher;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;


/**
 * Class Ws
 * @package ArrowWorker\Server
 */
class Ws extends ServerPattern
{

    const MODULE_NAME = 'Ws Server';

    /**
     * @var string
     */
    private $page404 = '';

    /**
     * @var string
     */
    private $documentRoot = '';

    /**
     * @var string
     */
    private $sslCertFile = '';

    /**
     * @var string
     */
    private $sslKeyFile = '';

    /**
     * @var int
     */
    private $maxRequest = 10000;

    /**
     * @var bool
     */
    private $isEnableCORS = true;

    /**
     * @var bool
     */
    private $isEnableHttp2 = false;

    private $isEnableStatic = true;

    /**
     * @var string
     */
    private $callback = '';

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @return void
     */
    public function Start()
    {
        $this->initServer();
        $this->initComponent(App::TYPE_WEBSOCKET);
        $this->initRouter();
        $this->setConfig();
        $this->onStart();
        $this->onOpen();
        $this->onMessage();
        $this->onWorkerStart();
        $this->onRequest();
        $this->onClose();
        $this->startServer();
    }

    /**
     * Http constructor.
     * @param Container $container
     * @param Log $logger
     * @param array $config
     */
    public function __construct(Container $container, Log $logger, array $config)
    {
        $this->container = $container;
        $this->logger = $logger;

        $this->port = $config['port'] ?? 8081;
        $this->mode = $config['mode'] ?? SWOOLE_PROCESS;
        $this->reactorNum = $config['reactorNum'] ?? 2;
        $this->workerNum = $config['workerNum'] ?? 2;
        $this->enableCoroutine = $config['enableCoroutine'] ?? true;
        $this->page404 = $config['404'] ?? '';
        $this->user = $config['user'] ?? 'root';
        $this->group = $config['group'] ?? 'root';
        $this->backlog = $config['backlog '] ?? 1024 * 100;
        $this->isEnableStatic = $config['isEnableStatic'] ?? false;
        $this->documentRoot = $config['documentRoot'] ?? '';
        $this->sslCertFile = $config['sslCertFile'] ?? '';
        $this->sslKeyFile = $config['sslKeyFile'] ?? '';
        $this->maxRequest = $config['maxRequest'] ?? 1000;
        $this->maxCoroutine = $config['maxCoroutine'] ?? 1000;
        $this->isEnableCORS = $config['isEnableCORS'] ?? true;;
        $this->isEnableHttp2 = $config['isEnableHttp2'] ?? false;;
        $this->pipeBufferSize = $config['pipeBufferSize'] ?? 1024 * 1024 * 100;
        $this->socketBufferSize = $config['socketBufferSize'] ?? 1024 * 1024 * 100;
        $this->maxContentLength = $config['maxContentLength'] ?? 1024 * 1024 * 10;
        $this->components = $config['components'] ?? [];

        $this->callback = $config['callback'] ?? '';
        $this->identity = $config['identity'];
    }

    private function startServer()
    {
        $this->server->start();
    }

    private function initServer()
    {
        $this->server = new Server(
            $this->host,
            $this->port,
            $this->mode,
            $this->isSsl() ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP
        );
    }

    private function initRouter()
    {
        $this->dispatcher = $this->container->Make(dispatcher::class, [$this->container, $this->page404]);
    }

    private function isSsl()
    {
        if (!file_exists($this->sslCertFile) || !file_exists($this->sslKeyFile)) {
            return false;
        }
        return true;
    }

    private function onStart()
    {
        $this->server->on('start', function ($server) {
            Process::SetName("{$this->identity}_Ws:{$this->port} Manager");
            Log::Dump("listening at port {$this->port}", Log::TYPE_DEBUG, self::MODULE_NAME);
        });
    }

    private function onOpen()
    {
        $this->server->on('open', function (Server $server, SwRequest $request) {
            $this->component->InitRequest($request, null);
            ("{$this->callback}::Open")($server, $request->fd);
            $this->component->Release();
        });
    }

    private function onMessage()
    {
        $this->server->on('message', function (Server $server, Frame $frame) {
            $this->component->Init();
            ("{$this->callback}::Message")($server, $frame);
            $this->component->Release();
        });
    }

    private function onClose()
    {
        $this->server->on('close', function (Server $server, int $fd) {
            $this->component->Init();
            ("{$this->callback}::Close")($server, $fd);
            $this->component->Release();
        });
    }

    private function onWorkerStart()
    {
        $this->server->on('WorkerStart', function () {
            Process::SetName("{$this->identity}_Ws:{$this->port} Worker");
            $this->component->InitWebWorkerStart($this->components, (bool)$this->isEnableCORS);
        });
    }

    private function onRequest()
    {
        $this->server->on('request', function (SwRequest $request, SwResponse $response) {
            $this->component->InitRequest($request, $response);
            $this->dispatcher->Go();
            $this->component->Release();;
        });
    }

    private function setConfig()
    {
        $this->server->set([
            'worker_num'            => $this->workerNum,
            'daemonize'             => false,
            'backlog'               => $this->backlog,
            'user'                  => $this->user,
            'group'                 => $this->group,
            'package_max_length'    => $this->maxContentLength,
            'enable_static_handler' => $this->isEnableStatic,
            'reactor_num'           => $this->reactorNum,
            'pipe_buffer_size'      => $this->pipeBufferSize,
            'socket_buffer_size'    => $this->socketBufferSize,
            'max_request'           => $this->maxRequest,
            'enable_coroutine'      => $this->enableCoroutine,
            'max_coroutine'         => $this->maxCoroutine,
            'document_root'         => $this->documentRoot,
            'log_file'              => $this->logger->GetStdOutFilePath(),
            'ssl_cert_file'         => $this->sslCertFile,
            'ssl_key_file'          => $this->sslKeyFile,
            'mode'                  => $this->mode,
            'open_http2_protocol'   => $this->isEnableHttp2,
            'hook_flags'            => SWOOLE_HOOK_ALL

        ]);
    }


}