<?php
/**
 * By yubin at 2020-03-22 11:16.
 */

namespace ArrowWorker\Web\Router;

use ArrowWorker\Config;
use ArrowWorker\Container;
use ArrowWorker\Library\ClassMethodChecker;
use ArrowWorker\Log\Log;
use ArrowWorker\Web\Request;

class RestRouter implements RouterInterface
{

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $regularPatternAndParams = [];

    private $container;


    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->loadConfig();
        $this->buildRegularPattern();
    }

    private function loadConfig()
    {
        $config = Config::Get('WebRouter');
        if (false === $config) {
            Log::Dump("Load rest api configuration failed", Log::TYPE_WARNING, __METHOD__);
            return;
        }
        if (!is_array($config)) {
            Log::Dump(" rest api configuration format is incorrect.", Log::TYPE_WARNING, __METHOD__);
            return;
        }

        foreach ($config as $serverNames => $restMap) {
            if (!is_array($restMap)) {
                continue;
            }
            $restAlias      = $this->rebuildGroup($restMap);
            $serverNameList = explode(',', $serverNames);
            foreach ($serverNameList as $serverName) {
                $this->config[trim($serverName)] = $restAlias;
            }
        }
    }

    public function GetConfig():array
    {
        return $this->config;
    }

    private function rebuildGroup(array $restMap): array
    {
        $restAlias = [];
        foreach ($restMap as $uri => $alias) {
            if (!is_array($alias)) {
                continue;
            }

            $isGroup = true;
            foreach ($alias as $requestMethod => $classMethod) {
                $requestMethod = strtoupper($requestMethod);
                if (!in_array($requestMethod, [
                    'GET',
                    'POST',
                    'DELETE',
                    'PUT',
                ])) {
                    continue;
                }

                //route to file
                if (is_string($classMethod)) {
                    if (!file_exists($classMethod)) {
                        continue;
                    }
                    $restAlias[$uri][$requestMethod] = file_get_contents($classMethod);

                }

                //wrong route setting
                if (!is_array($classMethod) || count($classMethod) < 2) {
                    continue;
                }

                [$class, $method] = $classMethod;
                $isSettingCorrect = ClassMethodChecker::IsClassMethodExists($class, $method);
                if (!$isSettingCorrect) {
                    Log::Dump("{$class} or {$method} does not exists", Log::TYPE_WARNING, __METHOD__);
                    continue;
                }

                $restAlias[$uri][$requestMethod] = [$class, $method];
                $isGroup                         = false;
                continue;

            }

            if (!$isGroup) {
                continue;
            }

            $subAlias = $this->rebuildGroup($alias);
            if (empty($subAlias)) {
                continue;
            }

            foreach ($subAlias as $subUri => $subFunctions) {
                $restAlias[$uri . $subUri] = $subFunctions;
            }
        }
        return $restAlias;
    }


    private function buildRegularPattern(): void
    {
        foreach ($this->config as $serverName => $restMap) {
            foreach ($restMap as $uri => $alias) {
                $matchExpression = preg_replace([
                    '/:\w+/',
                    '/\//',
                ], [
                    '[a-zA-Z0-9_-]+',
                    '\\/',
                ], $uri);

                $this->regularPatternAndParams[$serverName][$this->getUriKey($uri)]["/^{$matchExpression}$/"] = [
                    'uri'    => $uri,
                    'params' => $this->getUriParameterPositionAndName($uri),
                ];
            }
        }
    }

    /**
     * @param string $serverName
     * @return array
     */
    private function getUriKeyAndParameters(string $serverName): array
    {
        $uri     = Request::Uri();
        $nodes   = explode('/', $uri);
        $nodeLen = count($nodes);

        for ($i = $nodeLen; $i > 1; $i--) {
            $key = '/' . implode('/', array_slice($nodes, 1, $i - 1));
            if (!isset($this->regularPatternAndParams[$serverName][$key])) {
                continue;
            }

            $nodeMap = $this->regularPatternAndParams[$serverName][$key];
            foreach ($nodeMap as $match => $eachNode) {
                $isMatched = preg_match($match, $uri);
                if (false === $isMatched || $isMatched === 0) {
                    continue;
                }

                $params = [];
                foreach ($eachNode['params'] as $index => $param) {
                    $params[$param] = $nodes[$index];
                }
                return [
                    $eachNode['uri'],
                    $params,
                ];
            }
        }
        return [
            '',
            [],
        ];
    }

    private function getUriParameterPositionAndName(string $uri): array
    {
        $params    = [];
        $pathNodes = explode('/', $uri);

        foreach ($pathNodes as $index => $param) {
            if (false === strpos($param, ':')) {
                continue;
            }
            $params[$index] = str_replace(':', '', $param);
        }
        return $params;
    }

    public function getUriKey(string $uri): string
    {
        $colonPos = strpos($uri, ':');
        return (false === $colonPos) ? $uri : substr($uri, 0, $colonPos - 1);
    }

    public function Match(): ?MatchResult
    {
        $serverName = Request::Host();
        if (!isset($this->config[$serverName])) {
            return null;
        }

        [$uri, $params] = $this->getUriKeyAndParameters($serverName);
        $requestMethod = Request::Method();
        $serverName    = Request::Host();

        if (empty($uri)) {
            return null;
        }

        if (!isset($this->config[$serverName][$uri][$requestMethod])) {
            return null;
        }

        Request::SetParams($params, 'REST');
        return $this->container->Make(
            MatchResult::class,
            [
                $serverName,
                $uri,
                $requestMethod,
                $this->config[$serverName][$uri][$requestMethod][0],
                $this->config[$serverName][$uri][$requestMethod][1]
            ]
        );
    }

}