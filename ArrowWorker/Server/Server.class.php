<?php
/**
 * By yubin at 2019-11-17 10:25.
 */

namespace ArrowWorker\Server;


use ArrowWorker\Component;

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
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 8888;

    /**
     * @var int
     */
    protected $_reactorNum = 2;

    /**
     * @var int
     */
    protected $_workerNum = 1;

    /**
     * @var bool
     */
    protected $_enableCoroutine = true;

    /**
     * @var string
     */
    protected $_user = 'www';

    /**
     * @var string
     */
    protected $_group = 'www';

    /**
     * @var int
     */
    protected $_backlog = 1024000;

    /**
     * @var int
     */
    protected $_mode = SWOOLE_PROCESS;

    /**
     * @var int
     */
    protected $_maxCoroutine = 1000;

    /**
     * @var int
     */
    protected $_pipeBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected $_socketBufferSize = 1024 * 1024 * 100;

    /**
     * @var int
     */
    protected $_maxContentLength = 1024 * 1024 * 10;

    /**
     * @var array
     */
    protected $_components = [];

    /**
     * @var \Swoole\Http\Server|\Swoole\WebSocket\Server|\Swoole\Server
     */
    protected $_server;


    /**
     * @var Component
     */
    protected $_component;

    /**
     *
     */
    protected function _initComponent()
    {
        $this->_component = Component::Init();
    }

}