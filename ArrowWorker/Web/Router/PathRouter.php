<?php
/**
 * By yubin at 2020-03-22 11:16.
 */

namespace ArrowWorker\Web\Router;

use ArrowWorker\App;
use ArrowWorker\Container;
use ArrowWorker\Library\ClassMethodChecker;
use ArrowWorker\Web\Request;

class PathRouter
{

    private $controller;

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->controller = $container->Get(App::class)->GetController();
    }

    public function Match()
    {
        $uri = Request::Uri();
        $pathInfo = explode('/', $uri);
        $pathLen = count($pathInfo);
        Request::SetParams([], 'PATH');

        if ($pathLen < 3) {
            return false;
        }

        if ($pathLen == 4 && $pathInfo[1] != '' && $pathInfo[2] != '' && $pathInfo[3] != '') {
            $class = $this->controller . $pathInfo[1] . '\\' . $pathInfo[2];
            $method = $pathInfo[3];
            if (ClassMethodChecker::IsClassMethodExists($class, $method)) {
                return [Request::Host(), $uri, Request::Method(), $class, $method];
            }
        }

        if ($pathLen >= 3 && $pathInfo[1] != '' && $pathInfo[2] != '') {
            $class = $this->controller . $pathInfo[1];
            $method = $pathInfo[2];
            if (ClassMethodChecker::IsClassMethodExists($class, $method)) {
                return [Request::Host(), $uri, Request::Method(), $class, $method];
            }
        }

        return false;
    }
}