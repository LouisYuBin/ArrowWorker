<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


class Response
{
    private static $response;

    public static function Init(swoole_http_response $response)
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
        header("content-type:application/json;charset=utf-8");
        exit(json_encode($data));
    }

    public static function Response(string $msg)
    {

    }

}