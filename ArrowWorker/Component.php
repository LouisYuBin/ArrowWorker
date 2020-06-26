<?php
/**
 * By yubin at 2019-09-02 17:35.
 */

namespace ArrowWorker;

use ArrowWorker\Client\Http\Pool as HttpPool;
use ArrowWorker\Client\Tcp\Pool as TcpPool;
use ArrowWorker\Client\Ws\Pool as WsPool;
use ArrowWorker\Component\Cache\Pool as CachePool;
use ArrowWorker\Component\Db\Pool as DbPool;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log\Log;
use ArrowWorker\Web\Request;
use ArrowWorker\Web\Response;
use ArrowWorker\Web\Session;
use ArrowWorker\Web\Upload;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;

/**
 * Class Component
 * @package ArrowWorker
 */
class Component
{

    /**
     *
     */
    private $poolAlias = [
        'DB'           => DbPool::class,
        'CACHE'        => CachePool::class,
        'TCP_CLIENT'   => TcpPool::class,
        'WS_CLIENT'    => WsPool::class,
        'HTTP2_CLIENT' => HttpPool::class,
    ];

    /**
     * @var array
     */
    private $components = [];

    /**
     * @var Container $container
     */
    private $container;

    /**
     * @var Log $logger
     */
    private $logger;

    public function __construct(Container $container, Log $logger, int $type)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function Init()
    {
        $this->logger->Init();
        Coroutine::Init();
    }

    public function InitRequest(SwRequest $request, ?SwResponse $response)
    {
        $this->Init();
        Request::Init($request, $response);
    }

    public function InitWebWorkerStart(array $components, bool $isEnableCORS)
    {
        $this->InitPool($components);
        $this->container->Get(Session::class, [$this->container]);
        Upload::Init();
        Response::SetCORS($isEnableCORS);
    }

    /**
     * @param array $components
     */
    public function InitPool(array $components)
    {
        $this->logger->Init();
        foreach ($components as $key => $config) {
            $component = $this->poolAlias[strtoupper($key)] ?? '';
            if ('' === $component) {
                continue;
            }

            $this->components[] = $this->container->Get($component, [$this->container, $config]);
        }
    }

    public function Release()
    {
        foreach ($this->components as $component) {
            $component->Release();
        }
    }

}