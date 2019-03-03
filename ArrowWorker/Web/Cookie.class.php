<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Swoole;
use ArrowWorker\Lib\Crypto\CryptoArrow;

/**
 * Class Cookie
 * @package ArrowWorker
 */
class Cookie
{
    /**
     * swoole response handler
     * @var null
     */
    private static $repsonse = null;

    /**
     * cookie prefix
     * @var string
     */
    private static $prefix = "";

    /**
     * default cookie prefix
     * @var string
     */
    private static $defaultPrefix = "louis";

    /**
     * Init : init cookie and swoole response handler
     * @param array $cookies
     */
    public static function Init(array $cookies)
    {
        $_COOKIE[ Swoole::GetCid() ] = $cookies;
    }

    /**
     * Get : get specified cookie by $key
     * @param string $name
     * @return bool|string
     */
    public static function Get(string $key)
	{
		if( isset($_COOKIE[Swoole::GetCid()][$key]) )
		{
			return CryptoArrow::Decrypt($_COOKIE[Swoole::GetCid()][$key]);
		}
		return false;
	}

    /**
     * Set : set cookie
     * @param string $name
     * @param string $val
     * @param int $expireSeconds
     * @param string $path
     * @param null $domain
     * @return bool
     */
    public static function Set(string $name, string $val, int $expireSeconds=0, string $path='/', string $domain=null, bool $secure=false, bool $httpOnly=true) : bool
	{
		$expire = ($expireSeconds==0) ? 0 : time()+$expireSeconds;
		$val    = CryptoArrow::Encrypt($val);
        return Response::Cookie($name, $val, $expire, $path, $domain, $secure, $httpOnly);
	}

    /**
     * All : get all cookies
     * @return array
     */
    public static function All() : array
	{
		return $_COOKIE[ Swoole::GetCid() ];
	}



}