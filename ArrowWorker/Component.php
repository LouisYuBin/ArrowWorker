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

use ArrowWorker\Std\Http\RequestInterface;
use ArrowWorker\Std\Http\ResponseInterface;

use ArrowWorker\HttpServer\Request;
use ArrowWorker\HttpServer\Response;
use ArrowWorker\HttpServer\Session;
use ArrowWorker\Library\Context;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log\Log;

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
    private array $poolAlias = [
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

    /**
     * Component constructor.
     * @param Container $container
     * @param Log $logger
     * @param int $type
     */
    public function __construct(Container $container, Log $logger, int $type)
    {
        $this->container = $container;
        $this->logger    = $logger;
    }


    /**
     * @param SwRequest|null $request
     * @param SwResponse|null $response
     */
    public function init(?SwRequest $request = null, ?SwResponse $response = null): void
    {
        Log::initId();
        Coroutine::init();
        if (!is_null($request)) {
            Context::set(RequestInterface::class, $this->container->make(Request::class, [$request]));
            Context::set(ResponseInterface::class, $this->container->make(Response::class, [$response]));
        }
    }

    /**
     * @param array $components
     * @param bool $isEnableCORS
     */
    public function initOnWebWorkerStart(array $components, bool $isEnableCORS): void
    {
        $this->initPool($components);
        $this->container->get(Session::class, [$this->container]);
    }

    /**
     * @param array $components
     */
    public function initPool(array $components): void
    {
        Log::initId();
        foreach ($components as $key => $config) {
            $component = $this->poolAlias[strtoupper($key)] ?? '';
            if ('' === $component) {
                continue;
            }

            $this->components[] = $this->container->get($component, [$this->container, $config]);
        }
    }

    /**
     * @return void
     */
    public function release(): void
    {
        foreach ($this->components as $component) {
            $component->Release();
        }
    }

}