<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


class Request
{
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

    public static function File($file)
    {
        //move_uploaded_file()

    }

}