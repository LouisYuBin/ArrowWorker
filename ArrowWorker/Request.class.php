<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


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
        $_GET    = $get;
        $_POST   = $post;
        $_FILES  = $files;
        $_SERVER = $server;
    }

    /**
     * Method:return current request method(get/post/put/delete...)
     * @return mixed
     */
    public static function Method() : string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get : return specified get data
     * @param string $key
     * @return string|bool
     */
    public static function Get(string $key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : false;
    }

    /**
     * Post : return specified post data
     * @param string $key
     * @return string|bool
     */
    public static function Post(string $key)
    {
        return ( !isset($_POST[$key]) ) ? false : $_POST[$key];
    }

    /**
     * Gets : return all get data
     * @return array
     */
    public static function Gets() : array
    {
        return $_GET;
    }

    /**
     * Posts : return all post data
     * @return array
     */
    public static function Posts() : array
    {
        return $_POST;
    }

    /**
     * Server : return specified server data
     * @param string $key
     * @return string|bool
     */
    public static function Server(string $key)
    {
        return ( !isset($_SERVER[$key]) ) ? false : $_SERVER[$key];
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Servers()
    {
        return $_SERVER;
    }

    /**
     * Servers : return all server data
     * @return Upload|false
     */
    public static function File(string $postName)
    {
        return ( !isset($_FILES[$postName]) ) ? false : new Upload($postName);
    }

    /**
     * Servers : return all server data
     * @return array
     */
    public static function Files()
    {
        return $_FILES;
    }

}