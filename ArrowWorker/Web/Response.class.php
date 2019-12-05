<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Console;
use ArrowWorker\Log;
use \Swoole\Http\Response as SwResponse;

use ArrowWorker\Library\Coroutine;


/**
 * Class Response
 * @package ArrowWorker
 */
class Response
{

    const LOG_NAME = 'Http';

    /**
     * @var array
     */
    private static $_response = [];
    
    /**
     * @var bool
     */
    private static $_isAllowCORS = false;

    /**
     * @param SwResponse $response
     */
    public static function Init(SwResponse $response)
    {
        self::$_response[Coroutine::Id()] = $response;
        self::Header('Supplier','Arrow, Louis');
        if( self::$_isAllowCORS )
        {
            self::AllowCORS();
        }
    }

    /**
     * @param bool $status
     */
    public static function SetCORS( bool $status=true) : void
    {
        self::$_isAllowCORS = $status;
    }

    /**
     * @return bool
     */
    public static function GetCORS() : bool
    {
        return self::$_isAllowCORS;
    }

    /**
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
     * @param string $msg
     */
    public static function Write(string $msg)
    {
        Log::Debug("Response : {$msg}", self::LOG_NAME);
        self::$_response[ Coroutine::Id() ]->end( $msg );
    }

    /**
     * @param string $key
     * @param string $val
     * @return void
     */
    public static function Header(string $key, string $val)
    {
        self::$_response[ Coroutine::Id() ]->header($key, $val);
    }

    public static function Status(int $status)
    {
        self::$_response[ Coroutine::Id() ]->status($status);
    }

    /**
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
        $expire = ($expire==0) ? 0 : time()+$expire;
        self::$_response[Coroutine::Id()]->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
        return true;
    }
    
    public static function AllowCORS()
    {
        self::Headers([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Headers' => 'Origin,X-Requested-With,x_requested_with,Content-Type,Accept',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS'
        ]);
    }
    
    public static function Release()
    {
        unset( self::$_response[Coroutine::Id()] );
    }

}