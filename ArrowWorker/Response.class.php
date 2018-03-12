<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


class Response
{
    private static $response=null;

    public static function Init(\Swoole\Http\Response $response)
    {
        static::$response = $response;
    }

    public static function Json(int $code, array $data=[], string $msg='')
    {
        static::jsonFormat([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg
        ]);
    }

    public static function jsonFormat(array $data)
    {
        static::Header("content-type","application/json;charset=utf-8");
        static::Write(json_encode($data));
    }

    public static function Write(string $msg)
    {
        if( is_null(static::$response) )
        {
            exit( $msg );
        }
        static::$response->end( $msg );
    }

    private static function Header(string $key, string $val)
    {
        if( is_null(static::$response) )
        {
            header("{$key}:{$val}");
            return ;
        }
        static::$response->header($key,$val);
    }

}