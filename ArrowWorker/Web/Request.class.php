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

    private static $_parameters = [];
    private static $_header     = [];

    /**
     * Init : init request data(post/get/files...)
     * @param array $get
     * @param array $post
     * @param array $server
     * @param array $files
     */
    public static function Init(array $get, array $post, array $server, array $files, array $header)
    {
        $coId = Swoole::GetCid();
        $_GET[    $coId ]  = $get;
        $_POST[   $coId ]  = $post;
        $_FILES[  $coId ]  = $files;
        $_SERVER[ $coId ]  = $server;
        static::$_parameters[$coId] = [];
        static::$_header[$coId]     = $header;

        $get    = json_encode($_GET[ $coId ], JSON_UNESCAPED_UNICODE);
        $post   = json_encode($_POST[ $coId ], JSON_UNESCAPED_UNICODE);
        $files  = json_encode($_FILES[ $coId ], JSON_UNESCAPED_UNICODE);
        $params = json_encode(static::$_parameters[$coId], JSON_UNESCAPED_UNICODE);
        $header = json_encode(static::$_header[$coId], JSON_UNESCAPED_UNICODE);
        Log::Debug("get : {$get} \n post : {$post} \n files : {$files} \n param : {$params} \n header : {$header}",'request');
        unset($get, $post, $files, $params, $header);
    }

    /**
     * Method:return current request method(get/post/put/delete...)
     * @return string
     */
    public static function Method() : string
    {
        return $_SERVER[ Swoole::GetCid() ]['request_method'];
    }

    /**
     * Get : return specified get data
     * @param string $key
     * @return string|bool
     */
    public static function Get(string $key) : string
    {
        return isset($_GET[Swoole::GetCid()][$key]) ? $_GET[Swoole::GetCid()][$key] : '';
    }

    /**
     * Post : return specified post data
     * @param string $key
     * @return string
     */
    public static function Post(string $key) : string
    {
        return ( !isset($_POST[Swoole::GetCid()][$key]) ) ? '' : $_POST[Swoole::GetCid()][$key];
    }


    /**
     * Param : return specified post data
     * @param string $key
     * @return string
     */
    public static function Param(string $key) : string
    {
        return ( !isset(static::$_parameters[Swoole::GetCid()][$key]) ) ? '' : static::$_parameters[Swoole::GetCid()][$key];
    }

    /**
     * Params : return specified post data
     * @return array
     */
    public static function Params() : array
    {
        return static::$_parameters[Swoole::GetCid()];
    }

    /**
     * Header : return specified post data
     * @param string $key
     * @return string
     */
    public static function Header(string $key) : string
    {
        return ( !isset(static::$_header[Swoole::GetCid()][$key]) ) ? '' : static::$_header[Swoole::GetCid()][$key];
    }

    /**
     * Headers : return specified post data
     * @return array
     */
    public static function Headers() : array
    {
        return static::$_header[Swoole::GetCid()];
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets() : array
    {
        return $_GET[ Swoole::GetCid() ];
    }

    /**
     * Posts : return all post data
     * @return array
     */
    public static function Posts() : array
    {
        return $_POST[ Swoole::GetCid() ] ;
    }

    /**
     * Server : return specified server data
     * @param string $key
     * @return string|bool
     */
    public static function Server(string $key)
    {
        return ( !isset($_SERVER[Swoole::GetCid()][$key]) ) ? false : $_SERVER[Swoole::GetCid()][$key];
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
     * @param string $name
     * @return Upload|false
     */
    public static function File(string $name)
    {
        return ( !isset($_FILES[Swoole::GetCid()][$name]) ) ? false : new Upload($name);
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Files()
    {
        return $_FILES[ Swoole::GetCid() ];
    }

    public static function SetParams(array $params)
    {
        static::$_parameters[Swoole::GetCid()] = $params;
    }

    /**
     * release resource for request
     */
    public static function Release()
    {
        $coId = Swoole::GetCid();
        unset( $_GET[$coId], $_POST[$coId], $_FILES[$coId], $_SERVER[$coId], static::$_parameters[$coId]);
    }

}