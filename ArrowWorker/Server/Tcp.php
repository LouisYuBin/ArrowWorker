<?php
/**
 * User: louis
 * Time: 19-10-17 下午12:38
 */

namespace ArrowWorker\Server;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\Process;
use ArrowWorker\Log;
use ArrowWorker\Server\Server as ServerPattern;
use Swoole\Server;


/**
 * Class Tcp
 * @package ArrowWorker\Server
 */
class Tcp extends ServerPattern
{

    const MODULE_NAME = 'Tcp Server';

    /**
     * @var int|mixed
     */
    private $heartbeatCheckInterval = 30;


    /**
     * @var int|mixed
     */
    private $heartbeatIdleTime = 60;

    /**
     * @var bool|mixed
     */
    private $openEofCheck = true;

    /**
     * @var mixed|string
     */
    private $packageEof = '\r\n';

    /**
     * @var mixed|string
     */
    private $openEofSplit = '\r\n';

    /**
     * @var string
     */
    private $callback = '';

    /**
     * @var bool|mixed
     */
    private $isTcp6 = false;

    /**
     * @return void
     */
    public function Start()
    {
        $this->initServer();
        $this->initComponent(App::TYPE_TCP);
        $this->setConfig();
        $this->onStart();
        $this->onWorkerStart();
        $this->onConnect();
        $this->onReceive();
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

        $this->port = $config['port'] ?? 8082;
        $this->mode = $config['mode'] ?? SWOOLE_PROCESS;
        $this->reactorNum = $config['reactorNum'] ?? 2;
        $this->workerNum = $config['workerNum'] ?? 2;
        $this->enableCoroutine = $config['enableCoroutine'] ?? true;
        $this->user = $config['user'] ?? 'root';
        $this->group = $config['group'] ?? 'root';
        $this->backlog = $config['backlog '] ?? 1024 * 100;
        $this->maxCoroutine = $config['maxCoroutine'] ?? 1000;
        $this->pipeBufferSize = $config['pipeBufferSize'] ?? 1024 * 1024 * 100;
        $this->socketBufferSize = $config['socketBufferSize'] ?? 1024 * 1024 * 100;
        $this->maxContentLength = $config['maxContentLength'] ?? 1024 * 1024 * 10;

        $this->heartbeatCheckInterval = $config['heartbeatCheckInterval'] ?? 60;
        $this->heartbeatIdleTime = $config['heartbeatIdleTime'] ?? 30;
        $this->openEofCheck = $config['openEofCheck'] ?? false;
        $this->openEofSplit = $config['openEofSplit'] ?? false;
        $this->packageEof = $config['packageEof'] ?? '\r\n';

        $this->components = $config['components'] ?? [];

        $this->callback = $config['callback'];

        $this->isTcp6 = $config['isTcp6'] ?? false;

        $this->identity = $config['identity'];

    }

    /**
     *
     */
    private function startServer()
    {
        $this->server->start();
    }


    /**
     *
     */
    private function initServer()
    {
        $this->server = new Server(
            $this->host,
            $this->port,
            $this->mode,
            (bool)$this->isTcp6 ? SWOOLE_SOCK_TCP6 : SWOOLE_SOCK_TCP);
    }

    /**
     *
     */
    private function onStart()
    {
        $this->server->on('start', function ($server) {
            Process::SetName("{$this->identity}_Tcp:{$this->port} Manager");
            Log::Dump("listening at port {$this->port}", Log::TYPE_DEBUG, self::MODULE_NAME);
        });
    }

    /**
     *
     */
    private function onConnect()
    {
        $this->server->on('connect', function (Server $server, int $fd) {
            $this->component->Init();
            ("{$this->callback}::Connect")($server, $fd);
            $this->component->Release();
        });
    }

    /**
     *
     */
    private function onReceive()
    {
        $this->server->on('receive', function (Server $server, int $fd, int $reactor_id, string $data) {
            $this->component->Init();
            ("{$this->callback}::Receive")($server, $fd, $data);
            $this->component->Release();
        });
    }

    /**
     *
     */
    private function onClose()
    {
        $this->server->on('close', function (Server $server, int $fd) {
            $this->component->Init();
            ("{$this->callback}::Close")($server, $fd);
            $this->component->Release();
        });
    }

    /**
     *
     */
    private function onWorkerStart()
    {
        $this->server->on('WorkerStart', function () {
            Process::SetName("{$this->identity}_Tcp:{$this->port} Worker");
            $this->component->InitPool($this->components);
        });
    }

    /**
     *
     */
    private function setConfig()
    {
        $this->server->set([
            'mode'                     => $this->mode,
            'worker_num'               => $this->workerNum,
            'daemonize'                => false,
            'backlog'                  => $this->backlog,
            'user'                     => $this->user,
            'group'                    => $this->group,
            'package_max_length'       => $this->maxContentLength,
            'reactor_num'              => $this->reactorNum,
            'pipe_buffer_size'         => $this->pipeBufferSize,
            'socket_buffer_size'       => $this->socketBufferSize,
            'enable_coroutine'         => $this->enableCoroutine,
            'max_coroutine'            => $this->maxCoroutine,
            'log_file'                 => $this->logger->GetStdOutFilePath(),
            'heartbeat_check_interval' => $this->heartbeatCheckInterval,
            'heartbeat_idle_time'      => $this->heartbeatIdleTime,
            'open_eof_check'           => $this->openEofCheck,
            'package_eof'              => $this->packageEof,
            'open_eof_split'           => $this->openEofSplit,
            'hook_flags'               => SWOOLE_HOOK_ALL

        ]);
    }

}