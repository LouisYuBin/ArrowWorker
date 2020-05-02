<?php
/**
 * By yubin at 2020-03-20 11:10.
 */

namespace ArrowWorker\Web;


use ArrowWorker\Log;

class UriParser
{
    private $serverNameUriPattern;

    public function InitPatternAndParameters(array $routerConfig)
    {
        foreach ($routerConfig as $serverName => $restMap) {
            foreach ($restMap as $uri => $alias) {
                $matchExpression = preg_replace([
                    '/:\w+/',
                    '/\//',
                ], [
                    '[a-zA-Z0-9_-]+',
                    '\\/',
                ], $uri);

                $this->serverNameUriPattern[$serverName][$this->GetUriKey($uri)]["/^{$matchExpression}$/"] = [
                    'uri'    => $uri,
                    'params' => $this->GetRestParameterPositionAndName($uri),
                ];
            }
        }
    }

    private function rebuildGroup(array $restMap)
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

                list($class, $method) = $classMethod;
                [
                    $controller,
                    $errorMsg,
                ] = $this->checkClassMethod($class, $method);
                if (!empty($errorMsg)) {
                    Log::Dump($errorMsg, Log::TYPE_WARNING, self::MODULE_NAME);
                    continue;
                }

                $restAlias[$uri][$requestMethod] = [
                    $class,
                    $method,
                ];
                $isGroup = false;
                continue;

            }

            if (!$isGroup) {
                continue;
            }

            $subAlias = $this->rebuildGroup($alias);
            if (0 == count($subAlias)) {
                continue;
            }

            foreach ($subAlias as $subUri => $subFunctions) {
                $restAlias[$uri . $subUri] = $subFunctions;
            }
        }
        return $restAlias;
    }

    public function GetUriKey(string $uri)
    {
        $colonPos = strpos($uri, ':');
        return (false === $colonPos) ? $uri : substr($uri, 0, $colonPos - 1);
    }

    public function GetRestParameterPositionAndName(string $uri)
    {
        $params = [];
        $pathNodes = explode('/', $uri);

        foreach ($pathNodes as $index => $param) {
            if (false === strpos($param, ':')) {
                continue;
            }
            $params[$index] = str_replace(':', '', $param);
        }
        return $params;
    }


}