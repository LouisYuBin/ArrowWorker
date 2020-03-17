<?php
/**
 * By yubin at 2019-11-17 10:25.
 */

namespace ArrowWorker\Server;


use ArrowWorker\Component;
use ArrowWorker\Container;
use ArrowWorker\Log;

/**
 * Class Server
 * @package ArrowWorker\Server
 */
class Server
{

    /**
     *
     */
    const TYPE_HTTP = 'Http';

    /**
     *
     */
    const TYPE_WEBSOCKET = 'Ws';

    /**
     *
     */
    const TYPE_TCP = 'Tcp';

    /**
     *
     */
    const TYPE_UDP = 'Udp';

    /**
     * @var string
     */
    protected $host = '0.0.0.0';

    /**
     * @var int
     */
    protected $port = 8888;

    /**
     * @var int
     */
    protected $reactorNum = 2;

    /**
     * @var int
     */
    protected $workerNum = 1;

    /**
     * @var bool
     */
    protected $enableCoroutine = true;

    /**
     * @var string
     */
    protected $user = 'www';

    /**
     * @var string
     */
    protected $group = 'www';

    /**
     * @var int
     */
    protected $backlog = 1024000;

    /**
     * @var int
     */
    protected $mode = SWOOLE_PROCESS;

    /**
     * @var int
     */
    protected $maxCoroutine = 1000;

    /**
     * @var int
     */
    protected $pipeBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected $socketBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected $maxContentLength = 1024 * 1024 * 10;

    /**
     * @var array
     */
    protected $components = [];

    /**
     * @var \Swoole\Http\Server|\Swoole\WebSocket\Server|\Swoole\Server
     */
    protected $server;


    /**
     * @var Component
     */
    protected $component;
    
    protected $identity = 0;
	
	/**
	 * @var Container
	 */
    protected $container;
	
	/**
	 * @var Log
	 */
    protected $logger;

    /**
     * @param int $type
     */
    protected function initComponent(int $type)
    {
        $this->_component = $this->container->Make(Component::class, [ $this->container, $this->logger, $type ]);
    }

}