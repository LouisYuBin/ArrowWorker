<?php

/**
 * User: louis
 * Time: 19-10-17 下午12:38
 */

namespace ArrowWorker\HttpServer;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\Context;
use ArrowWorker\Library\Process;
use ArrowWorker\Log\Log;
use ArrowWorker\Server\Server as ServerPattern;
use ArrowWorker\Std\Http\RequestInterface;
use ArrowWorker\Std\Http\ResponseInterface;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Swoole\Http\Server as SwHttpServer;


/**
 * Class Http
 * @package ArrowWorker\Server
 */
class Server extends ServerPattern
{


    /**
     * @var string
     */
    private string $page404 = '';

    /**
     * @var bool
     */
    private bool $isEnableStatic = true;

    /**
     * @var string
     */
    private string $documentRoot = '';

    /**
     * @var string
     */
    private string $sslCertFile;

    /**
     * @var string
     */
    private string  $sslKeyFile;

    /**
     * @var int
     */
    private int $maxRequest = 10000;

    /**
     * @var bool
     */
    private bool $isEnableCORS = true;

    /**
     * @var bool
     */
    private bool $isEnableHttp2 = false;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @return void
     */
    public function start(): void
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
        $this->reactorNum       = $config['reactor_num'] ?? 2;
        $this->workerNum        = $config['worker_num'] ?? 2;
        $this->enableCoroutine  = $config['enable_coroutine'] ?? 'On';
        $this->page404          = $config['404'] ?? '';
        $this->user             = $config['user'] ?? 'root';
        $this->group            = $config['group'] ?? 'root';
        $this->backlog          = $config['backlog '] ?? 1024 * 100;
        $this->isEnableStatic   = $config['is_enable_static'] ?? false;
        $this->documentRoot     = $config['document_root'] ?? '';
        $this->sslCertFile      = $config['ssl_cert_file'] ?? '';
        $this->sslKeyFile       = $config['ssl_key_file'] ?? '';
        $this->maxRequest       = $config['max_request'] ?? 1000;
        $this->maxCoroutine     = $config['max_coroutine'] ?? 1000;
        $this->isEnableCORS     = $config['is_enable_CORS'] ?? true;
        $this->isEnableHttp2    = $config['is_enable_http2'] ?? false;
        $this->pipeBufferSize   = $config['pipe_buffer_size'] ?? 1024 * 1024 * 100;
        $this->socketBufferSize = $config['socket_buffer_size'] ?? 1024 * 1024 * 100;
        $this->maxContentLength = $config['max_content_length'] ?? 1024 * 1024 * 10;
        $this->components       = $config['components'] ?? [];
        $this->identity         = $config['identity'];
    }

    /**
     *
     */
    private function startServer():void
    {
        $this->server->start();
    }

    /**
     *
     */
    private function initServer(): void
    {
        if (!file_exists($this->sslCertFile) || !file_exists($this->sslKeyFile)) {
            $this->sslCertFile = '';
            $this->sslKeyFile  = '';
        }

        $this->server = new SwHttpServer(
            $this->host,
            $this->port,
            $this->mode,
            $this->isEnabledSsl() ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP
        );
    }

    /**
     *
     */
    private function initRequestDispatcher(): void
    {
        $this->dispatcher = $this->container->make(Dispatcher::class, [$this->container, $this->page404]);
    }

    /**
     * @return bool
     */
    private function isEnabledSsl(): bool
    {
        return file_exists($this->sslCertFile) && file_exists($this->sslKeyFile);
    }

    /**
     *
     */
    private function onStart(): void
    {
        $this->server->on('start', function ($server) {
            Process::setName("{$this->identity}_Http:{$this->port} Manager");
            Log::Dump("listening at port {$this->port}", Log::TYPE_DEBUG, __METHOD__);
        });
    }

    /**
     *
     */
    private function onWorkerStart(): void
    {
        $this->server->on('WorkerStart', function () {
            Process::setName("{$this->identity}_Http:{$this->port} Worker");
            $this->component->initOnWebWorkerStart($this->components, (bool)$this->isEnableCORS);
        });
    }

    /**
     *
     */
    private function onRequest(): void
    {
        $this->server->on('request', function (SwRequest $request, SwResponse $response) {
            Log::initId();
            Context::set(RequestInterface::class, $this->container->make(Request::class, [$request]));
            Context::set(ResponseInterface::class, $this->container->make(Response::class, [$response, $this->isEnableCORS]));
            $this->dispatcher->run();
            $this->component->release();
        });
    }

    /**
     *
     */
    private function setConfig(): void
    {
        $options = [
            'worker_num'         => $this->workerNum,
            'daemonize'          => false,
            'backlog'            => $this->backlog,
            'user'               => $this->user,
            'group'              => $this->group,
            'package_max_length' => $this->maxContentLength,
            'reactor_num'        => $this->reactorNum,
            'socket_buffer_size' => $this->socketBufferSize,
            'max_request'        => $this->maxRequest,
            'enable_coroutine'   => $this->enableCoroutine,
            'max_coroutine'      => $this->maxCoroutine,
            'log_file'           => $this->logger->GetStdOutFilePath(),
            'hook_flags'         => SWOOLE_HOOK_ALL
        ];

        if ($this->isEnabledSsl()) {
            $options['ssl_cert_file'] = $this->sslCertFile;
            $options['ssl_key_file']  = $this->sslKeyFile;
        }

        if ($this->isEnableStatic && file_exists($this->documentRoot)) {
            $options['enable_static_handler'] = $this->isEnableStatic;
            $options['document_root']         = $this->documentRoot;
        }

        $this->server->set($options);
    }

}