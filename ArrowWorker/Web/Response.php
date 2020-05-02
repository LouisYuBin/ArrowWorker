<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Library\Context;
use ArrowWorker\Log;

/**
 * Class Response
 * @package ArrowWorker
 */
class Response
{

    const LOG_NAME = 'Http';

    /**
     * @var bool
     */
    private static $isAllowCORS = false;

    /**
     * @param bool $status
     */
    public static function SetCORS(bool $status = true): void
    {
        self::$isAllowCORS = $status;
    }

    /**
     * @return bool
     */
    public static function GetCORS(): bool
    {
        return self::$isAllowCORS;
    }

    /**
     * @param int $code
     * @param array $data
     * @param string $msg
     */
    public static function Json(int $code, array $data = [], string $msg = '')
    {
        self::Header("content-type", "application/json;charset=utf-8");
        self::Write(json_encode([
            'code' => $code,
            'data' => $data,
            'msg'  => $msg,
        ]));
    }

    /**
     * @param string $msg
     */
    public static function Write(string $msg)
    {
        if (self::$isAllowCORS) {
            self::AllowCORS();
        }
        self::Header('Server', 'Arrow, Louis!');
        Context::Get(__CLASS__)->end($msg);
        Log::Debug("Response : {$msg}", [], self::LOG_NAME);
    }

    /**
     * @param string $key
     * @param string $val
     * @return void
     */
    public static function Header(string $key, string $val)
    {
        Context::Get(__CLASS__)->header($key, $val);
    }

    public static function Status(int $status)
    {
        Context::Get(__CLASS__)->status($status);
    }

    /**
     * @param array $data
     * @return void
     */
    public static function Headers(array $data)
    {
        $response = Context::Get(__CLASS__);
        foreach ($data as $key => $val) {
            $response->header($key, $val);
        }
    }


    /**
     * @param string $name
     * @param string $val
     * @param int $expire
     * @param string $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public static function Cookie(string $name, string $val, int $expire = 0, string $path = '/', string $domain = null, bool $secure = false, bool $httpOnly = true)
    {
        $expire = ($expire == 0) ? 0 : time() + $expire;
        Context::Get(__CLASS__)->cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
        return true;
    }

    public static function AllowCORS()
    {
        self::Headers([
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Headers' => 'Origin,X-Requested-With,x_requested_with,Content-Type,Accept',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,OPTIONS',
        ]);
    }

}