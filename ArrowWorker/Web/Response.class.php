<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Swoole;


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
    private static $_response = [];

    /**
     * Init : init swoole response handler
     * @param \Swoole\Http\Response $response
     */
    public static function Init(\Swoole\Http\Response $response)
    {
        static::$_response[Swoole::GetCid()] = $response;
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
        static::$_response[Swoole::GetCid()]->end( $msg );
    }

    /**
     * Header : set response header
     * @param string $key
     * @param string $val
     * @return void
     */
    public static function Header(string $key, string $val)
    {
        static::$_response[Swoole::GetCid()]->header($key,$val);
    }


    public static function Cookie(string $name, string $val, int $expire=0, string $path='/', string $domain=null, bool $secure=false, bool $httpOnly=true)
    {
        static::$_response[Swoole::GetCid()]->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
        return true;
    }
    
    public static function Release()
    {
        unset(static::$_response[Swoole::GetCid()]);
    }

}