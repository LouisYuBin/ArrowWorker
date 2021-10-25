<?php
/**
 * By yubin at 2020-03-12 17:31.
 */

namespace ArrowWorker\HttpServer;


use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\ClassMethodChecker;
use ArrowWorker\Library\Http;
use ArrowWorker\Log\Log;
use ArrowWorker\HttpServer\Router\MatchResult;

/**
 * Class Middleware
 * @package ArrowWorker\HttpServer
 */
class Middleware
{

    /**
     * @var array
     */
    private $config = [
        'http'   => [],
        'class'  => [],
        'method' => []
    ];

    /**
     * @var array
     */
    private $httpMiddleware = [];

    /**
     * @var Container
     */
    private $container;

    /**
     * Middleware constructor.
     * @param Container $container
     * @param array $routerConfig
     */
    public function __construct(Container $container, array $routerConfig)
    {
        $this->container = $container;
        $this->initConfig();
        $this->matchRouterConfig($routerConfig);
    }

    /**
     *
     */
    private function initConfig(): void
    {
        $config = Config::get('Middleware');
        if (!is_array($config)) {
            Log::Dump('config is incorrect', Log::TYPE_WARNING, __METHOD__);
            return;
        }

        foreach ($this->config as $type => $setting) {
            if (!isset($config[$type]) || !is_array($config[$type])) {
                Log::Dump("config({$type}) is incorrect : " . json_encode($config), Log::TYPE_WARNING, __METHOD__);
                $config[$type] = [];
            }
        }

        $this->config = [
            'http'   => $this->parseUriConfig($config['http']),
            'class'  => $this->parseClassConfig($config['class']),
            'method' => $this->parseMethodConfig($config['method'])
        ];
    }

    /**
     * @param array $config
     * @return array
     */
    private function parseUriConfig(array $config): array
    {
        $parsedConfig = [];
        foreach ($config as $hosts => $hostConfig) {
            if (!is_array($hostConfig)) {
                continue;
            }

            $hostList = explode(',', $hosts);
            foreach ($hostList as $host) {
                $parsedConfig[trim($host)] = $this->parseEachHostConfig($hostConfig);
            }
        }
        return $parsedConfig;
    }

    /**
     * @param array $config
     * @return array
     */
    private function parseEachHostConfig(array $config): array
    {
        $parsedConfig = [];
        foreach ($config as $uri => $uriConfig) {
            if (!is_array($uriConfig)) {
                continue;
            }
            $uri                        = str_replace(['*', '/'], ['.*', '\/'], $uri);
            $parsedConfig["/^{$uri}$/"] = $this->parseEachUriConfig($uriConfig);
        }
        return $parsedConfig;
    }

    /**
     * @param array $config
     * @return array
     */
    private function parseEachUriConfig(array $config): array
    {
        $parsedConfig = [];
        foreach ($config as $methods => &$middleware) {
            if (!is_array($middleware)) {
                continue;
            }
            $this->filterIllegalMiddleware($middleware);

            $methodList = explode('|', $methods);
            foreach ($methodList as $index => &$method) {
                $method = strtoupper(trim($method));
                if ('*' === $method) {
                    foreach (Http::METHODS as $legalMethod) {
                        $methodList[] = $legalMethod;
                    }
                }

                if (!in_array($method, Http::METHODS)) {
                    unset($methodList[$index]);
                    continue;
                }
                $parsedConfig[$method] = $middleware;
            }
        }
        return $parsedConfig;
    }

    /**
     * @param array $middlewareList
     */
    private function filterIllegalMiddleware(array &$middlewareList): void
    {
        foreach ($middlewareList as $index => $eachMiddleware) {
            if (!is_string($eachMiddleware) || !ClassMethodChecker::isClassMethodExists($eachMiddleware, 'Process')) {
                unset($middlewareList[$index]);
            }
        }
    }


    /**
     * @param array $config
     * @return array
     */
    private function parseClassConfig(array $config): array
    {
        $parsedConfig = [];
        foreach ($config as $class => &$middlewareList) {
            if (!class_exists($class)) {
                continue;
            }

            if (!is_array($middlewareList)) {
                continue;
            }
            $this->filterIllegalMiddleware($middlewareList);
            $parsedConfig[$class] = $middlewareList;
        }
        return $parsedConfig;
    }

    /**
     * @param array $config
     * @return array
     */
    private function parseMethodConfig(array $config): array
    {
        $parsedConfig = [];
        foreach ($config as $index => $eachMethodConfig) {
            if (!is_array($eachMethodConfig) || count($eachMethodConfig) < 3) {
                continue;
            }

            [$class, $method, $middlewareList] = $eachMethodConfig;
            if (!ClassMethodChecker::isClassMethodExists($class, $method) || !is_array($middlewareList)) {
                continue;
            }

            foreach ($middlewareList as $indexEachMiddleware => $middleware) {
                if (!ClassMethodChecker::isClassMethodExists($middleware, 'Process')) {
                    continue;
                }
                $parsedConfig["{$class}::{$method}"][] = $middleware;
            }
        }
        return $parsedConfig;
    }

    /**
     * @param MatchResult $matchResult
     * @return array
     */
    public function getList(MatchResult $matchResult): array
    {
        $host           = $matchResult->getServerName();
        $uri            = $matchResult->getUri();
        $class          = $matchResult->getActionClass();
        $method         = $matchResult->getActionMethod();
        $requestMethod  = $matchResult->getRequestMethod();

        $middlewareList = $this->httpMiddleware[$host][$uri][$requestMethod]??[];

        if (isset($this->config['class'][$class])) {
            $middlewareList = array_merge($middlewareList, $this->config['class'][$class]);
        }

        $classMethod = "{$class}::{$method}";
        if (isset($this->config['method'][$classMethod])) {
            $middlewareList = array_merge($middlewareList, $this->config['method'][$classMethod]);
        }

        return $middlewareList;
    }

    /**
     * @param array $routerConfig
     */
    private function matchRouterConfig(array $routerConfig):void
    {
        foreach ($this->config['http'] as $middlewareServerName => $middlewareUriConfig) {
            if (!isset($routerConfig[$middlewareServerName])) {
                continue;
            }

            foreach ($middlewareUriConfig as $middlewareUriPattern => $eachMiddlewareUriConfig) {
                foreach ($routerConfig[$middlewareServerName] as $routerUri => $routerList) {
                    $isUriMatchMiddleware = preg_match($middlewareUriPattern, $routerUri);
                    if (0 === $isUriMatchMiddleware || false === $isUriMatchMiddleware) {
                        continue;
                    }

                    foreach ($eachMiddlewareUriConfig as $middlewareRequestMethod => $middlewareList) {
                        if (!isset($routerConfig[$middlewareServerName][$routerUri][$middlewareRequestMethod])) {
                            continue;
                        }
                        $this->httpMiddleware[$middlewareServerName][$routerUri][$middlewareRequestMethod] = $middlewareList;
                    }
                }
            }
        }
    }


}