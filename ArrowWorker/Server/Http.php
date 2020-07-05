<?php

/**
 * User: louis
 * Time: 19-10-17 下午12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\Process;
use ArrowWorker\Log\Log;
use ArrowWorker\Server\Server as ServerPattern;
use ArrowWorker\Web\Dispatcher;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Http\Server;


/**
 * Class Http
 * @package ArrowWorker\Server
 */
class Http extends ServerPattern
{


    /**
     * @var string
     */
    private $page404 = '';

    /**
     * @var bool
     */
    private $isEnableStatic = true;

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

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @return void
     */
    public function Start(): void
    {
        $this->initServer();
        $this->initComponent(App::TYPE_HTTP);
        $this->initRequestDispatcher();
        $this->setConfig();
        $this->onStart();
        $this->onWorkerStart();
        $this->onRequest();
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
        $this->logger    = $logger;

        $this->port             = $config['port'] ?? 8080;
        $this->mode             = $config['mode'] ?? SWOOLE_PROCESS;
        $this->reactorNum       = $config['reactorNum'] ?? 2;
        $this->workerNum        = $config['workerNum'] ?? 2;
        $this->enableCoroutine  = $config['enableCoroutine'] ?? true;
        $this->page404          = $config['404'] ?? '';
        $this->user             = $config['user'] ?? 'root';
        $this->group            = $config['group'] ?? 'root';
        $this->backlog          = $config['backlog '] ?? 1024 * 100;
        $this->isEnableStatic   = $config['isEnableStatic'] ?? false;
        $this->documentRoot     = $config['documentRoot'] ?? '';
        $this->sslCertFile      = $config['sslCertFile'] ?? '';
        $this->sslKeyFile       = $config['sslKeyFile'] ?? '';
        $this->maxRequest       = $config['maxRequest'] ?? 1000;
        $this->maxCoroutine     = $config['maxCoroutine'] ?? 1000;
        $this->isEnableCORS     = $config['isEnableCORS'] ?? true;
        $this->isEnableHttp2    = $config['isEnableHttp2'] ?? false;
        $this->pipeBufferSize   = $config['pipeBufferSize'] ?? 1024 * 1024 * 100;
        $this->socketBufferSize = $config['socketBufferSize'] ?? 1024 * 1024 * 100;
        $this->maxContentLength = $config['maxContentLength'] ?? 1024 * 1024 * 10;
        $this->components       = $config['components'] ?? [];
        $this->identity         = $config['identity'];
    }

    private function startServer()
    {
        $this->server->start();
    }

    private function initServer(): void
    {
        if (!file_exists($this->sslCertFile) || !file_exists($this->sslKeyFile)) {
            $this->sslCertFile = '';
            $this->sslKeyFile  = '';
        }

        $this->server = new Server(
            $this->host,
            $this->port,
            $this->mode,
            $this->isSsl() ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP
        );
    }

    private function initRequestDispatcher(): void
    {
        $this->dispatcher = $this->container->Make(Dispatcher::class, [$this->container, $this->page404]);
    }

    /**
     * @return bool
     */
    private function isSsl(): bool
    {
        return !file_exists($this->sslCertFile) || !file_exists($this->sslKeyFile);
    }

    private function onStart(): void
    {
        $this->server->on('start', function ($server) {
            Process::SetName("{$this->identity}_Http:{$this->port} Manager");
            Log::Dump("listening at port {$this->port}", Log::TYPE_DEBUG, __METHOD__);
        });
    }

    private function onWorkerStart(): void
    {
        $this->server->on('WorkerStart', function () {
            Process::SetName("{$this->identity}_Http:{$this->port} Worker");
            $this->component->InitWebWorkerStart($this->components, (bool)$this->isEnableCORS);
        });
    }

    private function onRequest(): void
    {
        $this->server->on('request', function (SwRequest $request, SwResponse $response) {
            $this->component->InitRequest($request, $response);
            $this->dispatcher->Run();
            $this->component->Release();
        });
    }

    private function setConfig(): void
    {
        $options = [
            'worker_num'          => $this->workerNum,
            'daemonize'           => false,
            'backlog'             => $this->backlog,
            'user'                => $this->user,
            'group'               => $this->group,
            'package_max_length'  => $this->maxContentLength,
            'reactor_num'         => $this->reactorNum,
            'pipe_buffer_size'    => $this->pipeBufferSize,
            'socket_buffer_size'  => $this->socketBufferSize,
            'max_request'         => $this->maxRequest,
            'enable_coroutine'    => $this->enableCoroutine,
            'max_coroutine'       => $this->maxCoroutine,
            'log_file'            => $this->logger->GetStdOutFilePath(),
            'mode'                => $this->mode,
            'open_http2_protocol' => $this->isEnableHttp2,
            'ssl_cert_file'       => $this->sslCertFile,
            'ssl_key_file'        => $this->sslKeyFile,
            'hook_flags'          => SWOOLE_HOOK_ALL
        ];


        if ($this->isEnableStatic && file_exists($this->documentRoot)) {
            $options['enable_static_handler'] = $this->isEnableStatic;
            $options['document_root']         = $this->documentRoot;
        }

        $this->server->set($options);
    }

}