<?php
/**
 * Project ArrowWorker
 * User:   louis
 * Date:   18-1-1
 */

namespace ArrowWorker;


use ArrowWorker\Utilities\Crypto;

class Cookie
{
    private static $repsonse = null;
    private static $prefix = "";
    private static $defaultPrefix = "louis";

    public static function Init(array $cookies, \Swoole\Http\Response $response)
    {
        $_COOKIE = $cookies;
        static::$repsonse = $response;
    }

    public static function Get(string $name)
	{
		$name = static::getKey($name);
		if( isset($_COOKIE[$name]) )
		{
			return Crypto::Decrypt($_COOKIE[$name]);
		}
		return false;
	}

    public static function Set(string $name, string $val, int $expireSeconds=0, string $path='/',$domain=null)
	{
		$expire = ($expireSeconds==0) ? 0 : time()+$expireSeconds;
		$name   = static::getKey($name);
		$val    = Crypto::Encrypt($val);
		return static::SetByDriver($name, $val, $expire, $path, $domain);
	}

    public static function GetAll()
	{
		return $_COOKIE;
	}

    public static function getKey(string $name) : string
	{
	    if( !empty(static::$prefix) )
        {
            return md5(static::$prefix.$name);
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
		return md5(static::$prefix.$name);
	}

	private function SetByDriver(string $name, string $val, int $expire=0, string $path='/',$domain=null)
    {
        if( is_null(static::$repsonse) )
        {
            return setcookie($name, $val, $expire, $path, $domain);
        }
        return static::$repsonse->cookie($name, $val);
    }

}