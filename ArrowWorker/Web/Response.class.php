<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Coroutine;


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
     * @param bool $isAllowCORS
     */
    public static function Init(\Swoole\Http\Response $response, bool $isAllowCORS=false)
    {
        self::$_response[Coroutine::Id()] = $response;
        self::Header('Server','Arrow Web Server, V2.0, By Louis');
        if( $isAllowCORS )
        {
            self::AllowCORS();
        }
    }

    /**
     * Json : return formated json to browser
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public static function Json(int $code, array $data=[], string $msg='')
    {
        self::Header("content-type","application/json;charset=utf-8");
        self::Write(json_encode([
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
        self::$_response[Coroutine::Id()]->end( $msg );
    }

    /**
     * Header : set response header
     * @param string $key
     * @param string $val
     * @return void
     */
    public static function Header(string $key, string $val)
    {
        self::$_response[Coroutine::Id()]->header($key, $val);
    }

    /**
     * Header : set response header
     * @param array $data
     * @return void
     */
    public static function Headers(array $data)
    {
        $coId = Coroutine::Id();
        foreach ($data as $key=>$val)
        {
            self::$_response[ $coId ]->header($key, $val);
        }
        unset($coId);
    }


    /**
     * @param string      $name
     * @param string      $val
     * @param int         $expire
     * @param string      $path
     * @param string|null $domain
     * @param bool        $secure
     * @param bool        $httpOnly
     * @return bool
     */
    public static function Cookie( string $name, string $val, int $expire=0, string $path='/', string $domain=null, bool $secure=false, bool $httpOnly=true)
    {
        self::$_response[Coroutine::Id()]->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
        return true;
    }

    /**
     *
     */
    public static function AllowCORS()
    {
        self::Headers([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Headers' => 'Origin,X-Requested-With,x_requested_with,Content-Type,Accept',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS'
        ]);
    }

    /**
     *
     */
    public static function Release()
    {
        unset(self::$_response[Coroutine::Id()]);
    }

}