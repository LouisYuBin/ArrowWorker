<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Swoole;


/**
 * Class Request
 * @package ArrowWorker
 */
class Request
{

    /**
     * Init : init request data(post/get/files...)
     * @param array $get
     * @param array $post
     * @param array $server
     * @param array $files
     */
    public static function Init(array $get, array $post, array $server, array $files)
    {
        $_GET[    Swoole::GetCid() ]  = $get;
        $_POST[   Swoole::GetCid() ]  = $post;
        $_FILES[  Swoole::GetCid() ]  = $files;
        $_SERVER[ Swoole::GetCid() ]  = array_change_key_case($server,CASE_UPPER);
    }

    /**
     * Method:return current request method(get/post/put/delete...)
     * @return string
     */
    public static function Method() : string
    {
        return $_SERVER[ Swoole::GetCid() ]['REQUEST_METHOD'];
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

}