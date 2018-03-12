<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


use ArrowWorker\Utilities\Crypto;

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
     * @param \Swoole\Http\Response $response
     */
    public static function Init(array $cookies, \Swoole\Http\Response $response)
    {
        $_COOKIE = $cookies;
        static::$repsonse = $response;
    }

    /**
     * Get : get specified cookie by $key
     * @param string $name
     * @return bool|string
     */
    public static function Get(string $key)
	{
		$name = static::getKey($key);
		if( isset($_COOKIE[$key]) )
		{
			return Crypto::Decrypt($_COOKIE[$key]);
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
    public static function Set(string $name, string $val, int $expireSeconds=0, string $path='/', $domain=null)
	{
		$expire = ($expireSeconds==0) ? 0 : time()+$expireSeconds;
		$name   = static::getKey($name);
		$val    = Crypto::Encrypt($val);
		return static::SetByDriver($name, $val, $expire, $path, $domain);
	}

    /**
     * All : get all cookies
     * @return array
     */
    public static function All() : array
	{
		return $_COOKIE;
	}

    /**
     * getKey ：get encrypted key by original key
     * @param string $name
     * @return string
     */
    public static function getKey(string $key) : string
	{
	    if( !empty(static::$prefix) )
        {
            return md5(static::$prefix.$key);
        }

		$config = Config::App('Cookie');
		if( !$config )
		{
            static::$prefix = static::$defaultPrefix;
        }

		if( !isset($config['prefix']) )
        {
            static::$prefix = static::$defaultPrefix;
        }
		return md5(static::$prefix.$key);
	}

    /**
     * SetByDriver : set cookie by app type
     * @param string $name
     * @param string $val
     * @param int $expire
     * @param string $path
     * @param null $domain
     * @return bool
     */
    private function SetByDriver(string $name, string $val, int $expire=0, string $path='/', $domain=null)
    {
        if( is_null(static::$repsonse) )
        {
            return setcookie($name, $val, $expire, $path, $domain);
        }
        return static::$repsonse->cookie($name, $val);
    }

}