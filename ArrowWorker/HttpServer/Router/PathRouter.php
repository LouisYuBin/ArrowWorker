<?php
/**
 * By yubin at 2020-03-22 11:16.
 */

namespace ArrowWorker\HttpServer\Router;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\ClassMethodChecker;
use ArrowWorker\Library\Context;
use ArrowWorker\Std\Http\RequestInterface;
use ArrowWorker\Std\Http\RouterInterface;


class PathRouter implements RouterInterface
{

    private $controller;

    private Container $container;

    private RequestInterface $request;

    public function __construct(Container $container)
    {
        $this->container  = $container;
        $this->controller = $container->Get(App::class)->getDirName() . '\\' . APP_CONTROLLER_DIR . '\\';
    }

    public function match(): ?MatchResult
    {
        /**
         * @var $request RequestInterface
         */
        $request       = Context::get(RequestInterface::class);
        $host          = $request->getHost();
        $requestMethod = $request->getMethod();
        $uri           = $request->getUri();
        $pathInfo      = explode('/', $uri);
        $pathLen       = count($pathInfo);

        $request->setParams([], 'PATH');

        if ($pathLen < 3) {
            return null;
        }

        if (
            $pathLen === 4 &&
            $pathInfo[1] !== '' &&
            $pathInfo[2] !== '' &&
            $pathInfo[3] !== ''
        ) {
            $class  = $this->controller . $pathInfo[1] . '\\' . $pathInfo[2];
            $method = $pathInfo[3];
            if (ClassMethodChecker::isClassMethodExists($class, $method)) {
                return $this->container->make(MatchResult::class, [$host, $uri, $requestMethod, $class, $method]);
            }
        }

        if (
            $pathLen === 3 &&
            $pathInfo[1] !== '' &&
            $pathInfo[2] !== ''
        ) {
            $class  = $this->controller . $pathInfo[1];
            $method = $pathInfo[2];
            if (ClassMethodChecker::isClassMethodExists($class, $method)) {
                return $this->container->make(MatchResult::class, [$host, $uri, $requestMethod, $class, $method]);
            }
        }

        return null;
    }
}