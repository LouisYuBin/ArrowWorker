<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

declare(strict_types=1);


namespace ArrowWorker\Web;

use ArrowWorker\Library\Context;
use ArrowWorker\Log\Log;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;


/**
 * Class Request
 * @package ArrowWorker
 */
class Request
{

    const LOG_NAME = 'Http';

    /**
     * Init : init request data(post/get/files...)
     * @param SwRequest $request
     * @param SwResponse $response
     */
    public static function Init(SwRequest $request, ?SwResponse $response):void
    {
        Context::Set(self::class, $request);
        Context::Set(Response::class, $response);
        self::InitUrlPostParams($request);
    }

    private static function InitUrlPostParams(SwRequest $request)
    {
        if (!is_null($request->post)) {
            return;
        }

        $raw = $request->rawContent();
        if (empty($raw)) {
            return;
        }

        // normal x-www-form-urlencoded data
        if (substr($raw, 0, 1) != '{') {
            parse_str($raw, $postParam);
            $request->post = $postParam;
        } else {
            $postParam = json_decode($raw, true);
            if (is_array($postParam)) {
                $request->post = $postParam;
            }
        }
    }

    /**
     * @return string
     */
    public static function Method(): string
    {
        return Context::Get(__CLASS__)->server['request_method'];
    }

    /**
     * @return string
     */
    public static function Uri(): string
    {
        return Context::Get(__CLASS__)->server['request_uri'];
    }

    /**
     * @return string
     */
    public static function Raw(): string
    {
        return Context::Get(__CLASS__)->rawContent();
    }

    /**
     * @return string
     */
    public static function RouteType(): string
    {
        return Context::Get('routerType') ?? '';
    }

    /**
     * @return string
     */
    public static function QueryString(): string
    {
        return Context::Get(__CLASS__)->server['query_string'];
    }

    /**
     * @return string
     */
    public static function UserAgent(): string
    {
        return Context::Get(__CLASS__)->header['user-agent'];
    }


    /**
     * @return string
     */
    public static function ClientIp(): string
    {
        return Context::Get(__CLASS__)->server['remote_addr'];
    }

    /**
     * @param string $key
     * @param string $default
     * @return string|bool
     */
    public static function Get(string $key, string $default = ''): string
    {
        return Context::Get(__CLASS__)->get[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function Post(string $key, string $default = ''): string
    {
        return Context::Get(__CLASS__)->post[$key] ?? $default;
    }

    public static function Cookie(string $key, string $default = ''): string
    {
        return Context::Get(__CLASS__)->cookie[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function Param(string $key, string $default = ''): string
    {
        return Context::Get('urlParameters')[$key] ?? $default;
    }

    /**
     * Params : return specified post data
     * @return array
     */
    public static function Params(): array
    {
        return Context::Get('urlParameters') ?? [];
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function Header(string $key, string $default = ''): string
    {
        return Context::Get(__CLASS__)->header[$key] ?? $default;
    }

    public static function Host(): string
    {
        return Context::Get(__CLASS__)->header['host'] ?? '';
    }

    /**
     * @return array
     */
    public static function Headers(): array
    {
        return (array)Context::Get(__CLASS__)->header;
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets(): array
    {
        return (array)Context::Get(__CLASS__)->get;
    }

    /**
     * @return array
     */
    public static function Posts(): array
    {
        return (array)Context::Get(__CLASS__)->post;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function Server(string $key): string
    {
        return Context::Get(__CLASS__)->server[$key] ?? '';
    }

    /**
     * @return array
     */
    public static function Servers()
    {
        return (array)Context::Get(__CLASS__)->server;
    }

    /**
     * @param string $name
     * @return Upload|false
     */
    public static function File(string $name)
    {
        $file = Context::Get(__CLASS__)->files[$name];
        return is_null($file) ?
            false :
            new Upload((array)$file);
    }

    /**
     * @return array
     */
    public static function Files(): array
    {
        return (array)Context::Get(__CLASS__)->files;
    }

    /**
     * @param array $params
     * @param string $routeType path/rest
     */
    public static function SetParams(array $params, string $routeType = 'path')
    {
        Context::Set('urlParameters', $params);
        Context::Set('routerType', $routeType);
        self::log();
    }

    private static function log()
    {
        $request = Context::Get(__CLASS__);

        Log::Debug(' Request : {uri}[{method}], {request}',
            [
                'uri'     => $request->server['request_uri'],
                'method'  => $request->server['request_method'],
                'request' => json_encode($request, JSON_UNESCAPED_UNICODE),
            ]
            , self::LOG_NAME);
    }

}