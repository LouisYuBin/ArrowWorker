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
use ArrowWorker\Library\Context;
use ArrowWorker\Library\Coroutine;
use ArrowWorker\Log\Log;
use ArrowWorker\Web\Request\Request;
use ArrowWorker\Web\Request\RequestInterface;
use ArrowWorker\Web\Response\Response;
use ArrowWorker\Web\Response\ResponseInterface;
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
    public function Init(?SwRequest $request=null, ?SwResponse $response=null)
    {
        Log::InitId();
        Coroutine::Init();
        if(!is_null($request)) {
            Context::Set(RequestInterface::class, $this->container->Make(Request::class, [$request]));
            Context::Set(ResponseInterface::class, $this->container->Make(Response::class, [$response]));
        }
    }

    /**
     * @param array $components
     * @param bool $isEnableCORS
     */
    public function InitWebWorkerStart(array $components, bool $isEnableCORS)
    {
        $this->InitPool($components);
        $this->container->Get(Session::class, [$this->container]);
    }

    /**
     * @param array $components
     */
    public function InitPool(array $components)
    {
        Log::InitId();
        foreach ($components as $key => $config) {
            $component = $this->poolAlias[strtoupper($key)] ?? '';
            if ('' === $component) {
                continue;
            }

            $this->components[] = $this->container->Get($component, [$this->container, $config]);
        }
    }

    /**
     * @return void
     */
    public function Release():void
    {
        foreach ($this->components as $component) {
            $component->Release();
        }
    }

}