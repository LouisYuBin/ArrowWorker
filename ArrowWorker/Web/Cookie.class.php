<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Coroutine;
use ArrowWorker\Lib\Crypto\CryptoArrow;

/**
 * Class Cookie
 * @package ArrowWorker
 */
class Cookie
{

    /**
     * Init : init cookie and swoole response handler
     * @param array $cookies
     */
    public static function Init(array $cookies)
    {
        $_COOKIE[ Coroutine::Id() ] = $cookies;
    }

    /**
     * Get : get specified cookie by $key
     * @param string $key
     * @return bool|string
     */
    public static function Get(string $key)
	{
		if( isset($_COOKIE[Coroutine::Id()][$key]) )
		{
			return CryptoArrow::Decrypt($_COOKIE[Coroutine::Id()][$key]);
		}
		return false;
	}

    /**
     * Set : set cookie
     * @param string $name
     * @param string $val
     * @param int $expireSeconds
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
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
		return $_COOKIE[ Coroutine::Id() ];
	}

	public static function Release()
    {
        unset($_COOKIE[Coroutine::Id()]);
    }


}