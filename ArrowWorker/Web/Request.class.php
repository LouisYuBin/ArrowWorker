<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Log;
use ArrowWorker\Swoole;


/**
 * Class Request
 * @package ArrowWorker
 */
class Request
{

    /**
     * @var array
     */
    private static $_parameters = [];

    /**
     * @var array
     */
    private static $_header = [];

    /**
     * @var array
     */
    private static $_raw = [];

    private static $_routeType = [];

    /**
     * Init : init request data(post/get/files...)
     *
     * @param array  $get
     * @param array  $post
     * @param array  $server
     * @param array  $files
     * @param array  $header
     * @param string $raw
     */
    public static function Init(array $get, array $post, array $server, array $files, array $header, string $raw = '')
    {
        $coId = Swoole::GetCid();
        $_GET[ $coId ]    = $get;
        $_POST[ $coId ]   = $post;
        $_FILES[ $coId ]  = $files;
        $_SERVER[ $coId ] = $server;

        static::$_raw[ $coId ]        = $raw;
        static::$_header[ $coId ]     = $header;
        static::$_parameters[ $coId ] = [];

        static::InitUrlPostParams();

    }

    private static function InitUrlPostParams()
    {
        $coId = Swoole::GetCid();

        if (count($_POST[ $coId ]) > 0)
        {
            return;
        }

        $raw = static::$_raw[ $coId ];
        if (empty($raw))
        {
            return;
        }

        // normal x-www-form-urlencoded data
        if (substr($raw, 0, 1) != '{')
        {
            parse_str($raw, $postParam);
            $_POST[ $coId ] = $postParam;
        }
        else // json data
        {
            $postParam = json_decode($raw, true);
            if (is_array($postParam))
            {
                $_POST[ $coId ] = $postParam;
            }
        }
    }

    /**
     * Method:return current request method(get/post/put/delete...)
     * @return string
     */
    public static function Method(): string
    {
        return $_SERVER[ Swoole::GetCid() ]['request_method'];
    }

    /**
     * @return string
     */
    public static function Uri(): string
    {
        return $_SERVER[ Swoole::GetCid() ]['request_uri'];
    }


    /**
     * @return string
     */
    public static function Raw(): string
    {
        return static::$_raw[ Swoole::GetCid() ];
    }

    /**
     * @return string
     */
    public static function RouteType(): string
    {
        return static::$_routeType[ Swoole::GetCid() ];
    }

    /**
     * @return string
     */
    public static function QueryString(): string
    {
        return $_SERVER[ Swoole::GetCid() ]['query_string'];
    }

    /**
     * @return string
     */
    public static function UserAgent(): string
    {
        return static::$_header[ Swoole::GetCid() ]['user-agent'];
    }


    /**
     * @return string
     */
    public static function ClientIp(): string
    {
        return $_SERVER[ Swoole::GetCid() ]['remote_addr'];
    }

    /**
     * Get : return specified get data
     *
     * @param string $key
     *
     * @return string|bool
     */
    public static function Get(string $key): string
    {
        return isset($_GET[ Swoole::GetCid() ][ $key ]) ? $_GET[ Swoole::GetCid() ][ $key ] : '';
    }

    /**
     * Post : return specified post data
     *
     * @param string $key
     *
     * @return string
     */
    public static function Post(string $key): string
    {
        return (!isset($_POST[ Swoole::GetCid() ][ $key ])) ? '' : $_POST[ Swoole::GetCid() ][ $key ];
    }


    /**
     * Param : return specified post data
     *
     * @param string $key
     *
     * @return string
     */
    public static function Param(string $key): string
    {
        return (!isset(static::$_parameters[ Swoole::GetCid() ][ $key ])) ? '' :
            static::$_parameters[ Swoole::GetCid() ][ $key ];
    }

    /**
     * Params : return specified post data
     * @return array
     */
    public static function Params(): array
    {
        return static::$_parameters[ Swoole::GetCid() ];
    }

    /**
     * Header : return specified post data
     *
     * @param string $key
     *
     * @return string
     */
    public static function Header(string $key): string
    {
        return (!isset(static::$_header[ Swoole::GetCid() ][ $key ])) ? '' :
            static::$_header[ Swoole::GetCid() ][ $key ];
    }

    /**
     * Headers : return specified post data
     * @return array
     */
    public static function Headers(): array
    {
        return static::$_header[ Swoole::GetCid() ];
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets(): array
    {
        return $_GET[ Swoole::GetCid() ];
    }

    /**
     * Posts : return all post data
     * @return array
     */
    public static function Posts(): array
    {
        return $_POST[ Swoole::GetCid() ];
    }

    /**
     * Server : return specified server data
     *
     * @param string $key
     *
     * @return string|bool
     */
    public static function Server(string $key)
    {
        return (!isset($_SERVER[ Swoole::GetCid() ][ $key ])) ? false : $_SERVER[ Swoole::GetCid() ][ $key ];
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Servers()
    {
        return $_SERVER[ Swoole::GetCid() ];
    }

    /**
     * Servers : return all server data
     *
     * @param string $name
     *
     * @return Upload|false
     */
    public static function File(string $name)
    {
        return (!isset($_FILES[ Swoole::GetCid() ][ $name ])) ? false : new Upload($name);
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Files()
    {
        return $_FILES[ Swoole::GetCid() ];
    }

    /**
     * @param array $params
     * @param string $routeType path/rest
     */
    public static function SetParams(array $params, string $routeType='path')
    {
        static::$_parameters[ Swoole::GetCid() ] = $params;
        static::$_routeType[ Swoole::GetCid() ]  = $routeType;

        self::_logRequest();
    }

    /**
     * release resource for request
     */
    public static function Release()
    {
        $coId = Swoole::GetCid();
        unset($_GET[ $coId ], $_POST[ $coId ], $_FILES[ $coId ], $_SERVER[ $coId ], static::$_parameters[ $coId ], static::$_header[ $coId ], $coId);
    }

    private static function _logRequest()
    {
        $coId = Swoole::GetCid();
        $uri    = self::Uri();
        $raw    = self::Raw();
        $method = self::Method();
        $params = json_encode(self::$_parameters[$coId], JSON_UNESCAPED_UNICODE);
        $get    = json_encode($_GET[$coId], JSON_UNESCAPED_UNICODE);
        $post   = json_encode($_POST[$coId], JSON_UNESCAPED_UNICODE);
        $files  = json_encode($_FILES[$coId], JSON_UNESCAPED_UNICODE);
        $server = json_encode($_SERVER[$coId], JSON_UNESCAPED_UNICODE);
        $header = json_encode(self::$_header[$coId], JSON_UNESCAPED_UNICODE);

        $routeType = self::RouteType();

        Log::Debug(" {$uri} [{$method}:$routeType] \n Params : {$params} \n Get : {$get} \n Post : {$post} \n Header : {$header} \n Server : {$server} \n raw : {$raw} \n Files : {$files} ", 'request');
        unset($method, $get, $post, $files, $params, $header, $server);
    }

}