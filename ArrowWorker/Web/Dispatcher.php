<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker\Web;


use ArrowWorker\Console;
use ArrowWorker\Container;
use ArrowWorker\Web\Router\PathRouter;
use ArrowWorker\Web\Router\RestRouter;

/**
 * Class Router
 * @package ArrowWorker
 */
class Dispatcher
{
    /**
     *
     */
    const MODULE_NAME = 'Dispatcher';

    /**
     * @var string
     */
    private $page404 = 'page not found(该页面不存在).';

    /**
     * @var Container
     */
    private $container;

    private $isDebug = false;

    /**
     * @var Middleware
     */
    private $middleware;

    /**
     * @var array
     */
    private $routerClass = [
        RestRouter::class,
        PathRouter::class,
    ];

    /**
     * @var array
     */
    private $routers = [];

    /**
     * Dispatcher constructor.
     * @param Container $container
     * @param string $page404
     */
    public function __construct(Container $container, string $page404)
    {
        $this->container = $container;
        $this->initRouters();
        $this->init404($page404);
        $this->isDebug = $container->Get(Console::class)->IsDebug();
        $this->middleware = $container->Get(Middleware::class, [$container, $container->Get(RestRouter::class)->GetConfig()]);
    }

    private function initRouters()
    {
        foreach ($this->routerClass as $eachRouterClass) {
            $this->routers[] = $this->container->Get($eachRouterClass, [$this->container]);
        }
    }

    public function Go()
    {
        $result = false;
        foreach ($this->routers as $router) {
            $result = $router->Match();
            if (false !== $result) {
                break;
            }
        }

        // 404
        if (false === $result) {
            $result = $this->page404;
        }

        $this->dispatch($result);
    }

    /**
     * @param $matchResult
     */
    private function dispatch($matchResult)
    {
        $body = '';
        if (is_array($matchResult)) {
            [$host, $uri, $requestMethod, $controller, $method] = $matchResult;
            $this->middleware->GetMiddlewareList($host, $uri, $requestMethod, $controller, $method);

            $response = (new $controller)->$method();
            if (is_string($response)) {
                $body = $response;
            }

            if (is_array($response) || is_object($response)) {
                $body = json_encode($body, JSON_UNESCAPED_UNICODE);
            }
        }
        Response::Write($body);
    }

    /**
     * @param string $msg
     * @return bool
     */
    private function notFound(string $msg)
    {
        Response::Status(404);
        Response::Write($this->page404);
        return true;
    }


    /**
     * @param string $page404
     */
    private function init404(string $page404)
    {
        if (empty($page404) || !file_exists($page404)) {
            $this->page404 = file_get_contents(ArrowWorker . '/Static/404.html');
            return;
        }

        $this->page404 = file_get_contents($page404);
    }

}