<?php
/**
 * By yubin at 2019-11-17 10:25.
 */

namespace ArrowWorker\Server;


use ArrowWorker\Component;
use ArrowWorker\Container;
use ArrowWorker\Log\Log;

/**
 * Class Server
 * @package ArrowWorker\Server
 */
class Server
{

    /**
     *
     */
    public const TYPE_HTTP = 'Http';

    /**
     *
     */
    public const TYPE_WEBSOCKET = 'Ws';

    /**
     *
     */
    public const TYPE_TCP = 'Tcp';

    /**
     *
     */
    public const TYPE_UDP = 'Udp';

    /**
     * @var string
     */
    protected string $host = '0.0.0.0';

    /**
     * @var int
     */
    protected int $port = 8888;

    /**
     * @var int
     */
    protected int $reactorNum = 2;

    /**
     * @var int
     */
    protected int $workerNum = 1;

    /**
     * @var string $enableCoroutine
     */
    protected string $enableCoroutine = 'On';

    /**
     * @var string
     */
    protected string $user = 'www';

    /**
     * @var string
     */
    protected string $group = 'www';

    /**
     * @var int
     */
    protected int $backlog = 1024000;

    /**
     * @var int
     */
    protected int $mode = SWOOLE_PROCESS;

    /**
     * @var int
     */
    protected int $maxCoroutine = 1000;

    /**
     * @var int
     */
    protected int $pipeBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected int $socketBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected int $maxContentLength = 1024 * 1024 * 10;

    /**
     * @var array
     */
    protected array $components = [];

    /**
     * @var \Swoole\Http\Server|\Swoole\WebSocket\Server|\Swoole\Server
     */
    protected $server;


    /**
     * @var Component
     */
    protected Component $component;

    protected string $identity;

    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @var Log
     */
    protected Log $logger;

    /**
     * @param int $type
     */
    protected function initComponent(int $type): void
    {
        $this->component = $this->container->make(Component::class, [$this->container, $this->logger, $type]);
    }

}