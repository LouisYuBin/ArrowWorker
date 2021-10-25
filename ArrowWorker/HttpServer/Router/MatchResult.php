<?php
/**
 * By yubin at 2020-05-31 11:19.
 */

namespace ArrowWorker\HttpServer\Router;


use ArrowWorker\Container;

/**
 * Class MatchResult
 * @package ArrowWorker\HttpServer\Router
 */
class MatchResult
{

    /**
     *
     */
    private const PAGE_CONTENT_PREFIX = 'web:page:prefix:';
    /**
     * @var string
     */
    private string $serverName;

    /**
     * @var string
     */
    private string $uri;

    /**
     * @var string
     */
    private string $actionClass;

    /**
     * @var string
     */
    private string $requestMethod;

    /**
     * @var string
     */
    private string $actionMethod;

    /**
     * @var string
     */
    private string $pageContent = '';

    /**
     * @var Container
     */
    private Container $container;

    /**
     * MatchResult constructor.
     * @param Container $container
     * @param string $serverName
     * @param string $uri
     * @param string $requestMethod
     */
    public function __construct(Container $container, string $serverName, string $uri, string $requestMethod)
    {
        $this->container     = $container;
        $this->serverName    = $serverName;
        $this->uri           = $uri;
        $this->requestMethod = $requestMethod;
    }

    /**
     * @param string $actionClass
     * @return $this
     */
    public function setActionClass(string $actionClass): self
    {
        $this->actionClass = $actionClass;
        return $this;
    }

    /**
     * @param string $actionMethod
     * @return $this
     */
    public function setActionMethod(string $actionMethod): self
    {
        $this->actionMethod = $actionMethod;
        return $this;
    }


    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->serverName;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getActionClass(): string
    {
        return $this->actionClass;
    }

    /**
     * @return string
     */
    public function getActionMethod(): string
    {
        return $this->actionMethod;
    }

    /**
     * @return string
     */
    public function getPageContent(): string
    {
        return $this->pageContent;
    }

    /**
     * @param string $pageContent
     * @return $this
     */
    public function setPageContent(string $pageContent): self
    {
        if (empty($pageContent)) {
            return $this;
        }

        $pageContentKey = self::PAGE_CONTENT_PREFIX . "{$this->serverName}:{$this->uri}:{$this->requestMethod}";
        $cachedContent = $this->container->get($pageContentKey);
        if ($cachedContent !== null) {
            var_dump('cache');

            $this->pageContent = $cachedContent;
            return $this;
        }

        var_dump('initialize');
        $this->pageContent = $pageContent;
        $this->container->set($pageContentKey, $pageContent);
        return $this;
    }

}