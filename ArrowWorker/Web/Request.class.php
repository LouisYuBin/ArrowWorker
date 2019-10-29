<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use Swoole\Http\Request as SwRequest;

use ArrowWorker\Log;
use ArrowWorker\Lib\Coroutine;


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
     * @param SwRequest  $request
     */
    public static function Init( SwRequest $request)
    {
        $coId = Coroutine::Id();
        $_GET[ $coId ]    = is_array( $request->get ) ? $request->get : [];
        $_POST[ $coId ]   = is_array( $request->post ) ? $request->post : [];
        $_FILES[ $coId ]  = is_array( $request->files ) ? $request->files : [];
        $_SERVER[ $coId ] = is_array( $request->server ) ? $request->server : [];

        self::$_raw[ $coId ]        = $request->rawContent();
        self::$_header[ $coId ]     = is_array( $request->header ) ? $request->header : [];
        self::$_parameters[ $coId ] = [];

        self::InitUrlPostParams();

    }

    private static function InitUrlPostParams()
    {
        $coId = Coroutine::Id();

        if (count($_POST[ $coId ]) > 0)
        {
            return;
        }

        $raw = self::$_raw[ $coId ];
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
        return $_SERVER[ Coroutine::Id() ]['request_method'];
    }

    /**
     * @return string
     */
    public static function Uri(): string
    {
        return $_SERVER[ Coroutine::Id() ]['request_uri'];
    }


    /**
     * @return string
     */
    public static function Raw(): string
    {
        return self::$_raw[ Coroutine::Id() ];
    }

    /**
     * @return string
     */
    public static function RouteType(): string
    {
        return self::$_routeType[ Coroutine::Id() ];
    }

    /**
     * @return string
     */
    public static function QueryString(): string
    {
        return $_SERVER[ Coroutine::Id() ]['query_string'];
    }

    /**
     * @return string
     */
    public static function UserAgent(): string
    {
        return self::$_header[ Coroutine::Id() ]['user-agent'];
    }


    /**
     * @return string
     */
    public static function ClientIp(): string
    {
        return $_SERVER[ Coroutine::Id() ]['remote_addr'];
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
        return isset($_GET[ Coroutine::Id() ][ $key ]) ? $_GET[ Coroutine::Id() ][ $key ] : '';
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
        return (!isset($_POST[ Coroutine::Id() ][ $key ])) ? '' : $_POST[ Coroutine::Id() ][ $key ];
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
        return (!isset(self::$_parameters[ Coroutine::Id() ][ $key ])) ? '' :
            self::$_parameters[ Coroutine::Id() ][ $key ];
    }

    /**
     * Params : return specified post data
     * @return array
     */
    public static function Params(): array
    {
        return self::$_parameters[ Coroutine::Id() ];
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
        return (!isset(self::$_header[ Coroutine::Id() ][ $key ])) ? '' :
            self::$_header[ Coroutine::Id() ][ $key ];
    }

    /**
     * Headers : return specified post data
     * @return array
     */
    public static function Headers(): array
    {
        return self::$_header[ Coroutine::Id() ];
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets(): array
    {
        return $_GET[ Coroutine::Id() ];
    }

    /**
     * Posts : return all post data
     * @return array
     */
    public static function Posts(): array
    {
        return $_POST[ Coroutine::Id() ];
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
        return (!isset($_SERVER[ Coroutine::Id() ][ $key ])) ? false : $_SERVER[ Coroutine::Id() ][ $key ];
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Servers()
    {
        return $_SERVER[ Coroutine::Id() ];
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
        return (!isset($_FILES[ Coroutine::Id() ][ $name ])) ? false : new Upload($name);
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Files()
    {
        return $_FILES[ Coroutine::Id() ];
    }

    /**
     * @param array $params
     * @param string $routeType path/rest
     */
    public static function SetParams(array $params, string $routeType='path')
    {
        self::$_parameters[ Coroutine::Id() ] = $params;
        self::$_routeType[ Coroutine::Id() ]  = $routeType;

        self::_logRequest();
    }

    /**
     * release resource for request
     */
    public static function Release()
    {
        $coId = Coroutine::Id();
        unset($_GET[ $coId ], $_POST[ $coId ], $_FILES[ $coId ], $_SERVER[ $coId ], self::$_parameters[ $coId ], self::$_header[ $coId ], static::$_raw[$coId], static::$_routeType[$coId], $coId);
    }

    private static function _logRequest()
    {
        $coId = Coroutine::Id();
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