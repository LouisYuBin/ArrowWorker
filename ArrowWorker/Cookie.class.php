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

	static function Get(string $name)
	{
		$name   = static::getCookieKey($name);
		if( isset($_COOKIE[$name]) )
		{
			return Crypto::Decrypt($_COOKIE[$name]);
		}
		return false;
	}

	static function Set(string $name, string $val, int $expireSeconds=0, string $path='/',$domain=null)
	{
		$expire = ($expireSeconds==0) ? 0 : time()+$expireSeconds;
		$name   = static::getCookieKey($name);
		$val    = Crypto::Encrypt($val);
		return setcookie($name, $val, $expire, $path, $domain);
	}

	static function GetAll()
	{
		return $_COOKIE;
	}

	static function getCookieKey(string $name) : string
	{
		$config = Config::App('Cookie');
		if( $config )
		{
			$name = $config['prefix'].$name;
		}
		return md5($name);
	}

}