<?php
/**
 * By yubin at 2020-05-31 11:19.
 */

namespace ArrowWorker\Web\Router;


class MatchResult
{
    private $serverName;

    private $uri;

    private $controller;

    private $requestMethod;

    private $method;

    public function __construct(string $serverName, string $uri, string $requestMethod, string $controller, string $method)
    {
        $this->serverName = $serverName;
        $this->uri = $uri;
        $this->requestMethod = $requestMethod;
        $this->controller = $controller;
        $this->method = $method;
    }

    public function getServerName(): string
    {
        $this->serverName;
    }

    public function getUri(): string
    {
        $this->uri;
    }

    public function getRequestMethod(): string
    {
        $this->requestMethod;
    }

    public function getController(): string
    {
        $this->controller;
    }

    public function getMethod(): string
    {
        $this->method;
    }

}