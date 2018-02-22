<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


class Request
{

    public static function Init(array $get, array $post, array $server, array $files)
    {
        $_GET    = $get;
        $_POST   = $post;
        $_FILES  = $files;
        $_SERVER = $server;
    }

    public static function Method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function Get(string $key)
    {
        return ( !isset($_GET[$key]) ) ? false : $_GET[$key];
    }

    public static function Post(string $key)
    {
        return ( !isset($_POST[$key]) ) ? false : $_POST[$key];
    }


}