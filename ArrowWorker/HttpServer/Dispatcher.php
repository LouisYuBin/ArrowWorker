<?php
/**
 * User: louis
 * Date: 17-10-20
 * Time: 上午12:51
 */

namespace ArrowWorker\HttpServer;


use ArrowWorker\Container;
use ArrowWorker\HttpServer\Router\MatchResult;
use ArrowWorker\HttpServer\Router\PathRouter;
use ArrowWorker\HttpServer\Router\RestRouter;
use ArrowWorker\Library\Context;
use ArrowWorker\Std\Http\ResponseInterface;

/**
 * Class Router
 * @package ArrowWorker
 */
class Dispatcher
{

    /**
     * @var string
     */
    private string $page404 = 'page not found(该页面不存在).';

    /**
     * @var Container
     */
    private Container $container;

    /**
     * @var Middleware
     */
    private Middleware $middleware;

    /**
     * @var array
     */
    private array $routerClasses = [
        RestRouter::class,
        PathRouter::class,
    ];

    /**
     * @var array
     */
    private array $routers = [];

    private ResponseInterface $response;

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
        $this->middleware = $container->Get(Middleware::class, [$container, $container->Get(RestRouter::class)->GetConfig()]);
    }

    private function initRouters(): void
    {
        foreach ($this->routerClasses as $eachRouterClass) {
            $this->routers[] = $this->container->get($eachRouterClass, [$this->container]);
        }
    }

    public function run(): void
    {
        $result = null;
        /**
         * @var \ArrowWorker\Std\Http\RouterInterface $router
         */
        foreach ($this->routers as $router) {
            /**
             * @var MatchResult $result
             */
            $result = $router->match();
            if ($result instanceof MatchResult) {
                break;
            }
        }

        $this->dispatch($result);
    }

    /**
     * @param MatchResult $matchResult
     */
    private function dispatch(?MatchResult $matchResult): void
    {
        $responseBody = $this->page404;

        if (is_null($matchResult)) { //404
            Context::get(ResponseInterface::class)->write($responseBody);
            return;
        }

        $this->middleware->getList($matchResult);
        $actionClass  = $matchResult->getActionClass();
        $actionMethod = $matchResult->getActionMethod();
        $pageContent  = $matchResult->getPageContent();
        $responseBody = empty($pageContent) ? (new $actionClass)->$actionMethod() : $pageContent;
        if (is_array($responseBody)) {
            Context::get(ResponseInterface::class)->writeJson($responseBody);
            return;
        }

        Context::get(ResponseInterface::class)->write($responseBody);
    }


    /**
     * @param string $page404
     */
    private function init404(string $page404): void
    {
        if (empty($page404) || !file_exists($page404)) {
            $this->page404 = file_get_contents(ArrowWorker . '/Static/404.html');
            return;
        }

        $this->page404 = file_get_contents($page404);
    }

}