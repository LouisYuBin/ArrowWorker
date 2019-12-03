<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker\Web;

use ArrowWorker\Library\Coroutine;
use ArrowWorker\Library\Crypto\CryptoArrow;

/**
 * Class Cookie
 * @package ArrowWorker
 */
class Cookie
{

    private static $_data = [];
    /**
     * Init : init cookie and swoole response handler
     * @param array $cookies
     */
    public static function Init(array $cookies)
    {
        self::$_data[ Coroutine::Id() ] = $cookies;
    }

    /**
     * Get : get specified cookie by $key
     * @param string $key
     * @return string
     */
    public static function Get(string $key)
	{
		if( isset(self::$_data[Coroutine::Id()][$key]) )
		{
			return CryptoArrow::Decrypt(self::$_data[Coroutine::Id()][$key]);
		}
		return '';
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
		return self::$_data[ Coroutine::Id() ];
	}

	public static function Release()
    {
        unset(self::$_data[Coroutine::Id()]);
    }


}