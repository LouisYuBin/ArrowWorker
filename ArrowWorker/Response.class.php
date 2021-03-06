<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


/**
 * Class Response
 * @package ArrowWorker
 */
class Response
{
    /**
     * response handler for swoole
     * @var null
     */
    private static $response = null;

    /**
     * Init : init swoole response handler
     * @param \Swoole\Http\Response $response
     */
    public static function Init(\Swoole\Http\Response $response)
    {
        static::$response = $response;
    }

    /**
     * Json : return formated json to browser
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public static function Json(int $code, array $data=[], string $msg='')
    {
        static::Header("content-type","application/json;charset=utf-8");
        static::Write(json_encode([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg
        ]));
    }

    /**
     * Write : write data to browser
     * @param string $msg
     */
    public static function Write(string $msg)
    {
        if( is_null(static::$response) )
        {
            exit( $msg );
        }
        static::$response->end( $msg );
    }

    /**
     * Header : set response header
     * @param string $key
     * @param string $val
     * @return void
     */
    public static function Header(string $key, string $val)
    {
        if( is_null(static::$response) )
        {
            header("{$key}:{$val}");
            return ;
        }
        static::$response->header($key,$val);
    }

}