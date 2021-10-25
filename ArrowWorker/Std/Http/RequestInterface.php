<?php
/**
 * By yubin at 2020-06-26 12:50.
 */

namespace ArrowWorker\Std\Http;

use ArrowWorker\HttpServer\Upload;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return string
     */
    public function getUri(): string;

    /**
     * @return string
     */
    public function getRaw(): string;

    /**
     * @return string
     */
    public function getRouteType(): string;

    /**
     * @return string
     */
    public function getQueryString(): string;

    /**
     * @return string
     */
    public function getUserAgent(): string;


    /**
     * @return string
     */
    public function getClientIp(): string;

    /**
     * @param string $key
     * @param string $default
     * @return string|bool
     */
    public function get(string $key, string $default = ''): string;

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function post(string $key, string $default = ''): string;

    public function cookie(string $key, string $default = ''): string;

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getParam(string $key, string $default = ''): string;

    /**
     * Params : return specified post data
     * @return array
     */
    public function getParams(): array;

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getHeader(string $key, string $default = ''): string;

    public function getHost(): string;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Gets : return all get data
     * @return array
     */
    public function gets(): array;

    /**
     * @return array
     */
    public function posts(): array;

    /**
     * @param string $key
     * @return string
     */
    public function getServer(string $key): string;

    /**
     * @return array
     */
    public function getServers():array ;

    /**
     * @param string $name
     * @return Upload|false
     */
    public function getFile(string $name):?Upload;

    /**
     * @return array
     */
    public function getFiles(): array;

    /**
     * @param array $params
     * @param string $routeType path/rest
     */
    public function setParams(array $params, string $routeType = 'path'):void;

}